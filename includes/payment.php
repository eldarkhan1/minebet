<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class YooKassa {

    private string $shop_id;
    private string $secret;
    private string $api_url = 'https://api.yookassa.ru/v3/';

    public function __construct() {
        $this->shop_id = YOOKASSA_SHOP_ID;
        $this->secret  = YOOKASSA_SECRET;
    }

    private function request(string $method, string $endpoint, array $data = [], ?string $idempotence = null): array {
        $url = $this->api_url . $endpoint;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->shop_id . ':' . $this->secret,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                $idempotence ? 'Idempotence-Key: ' . $idempotence : null,
            ]),
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = json_decode($resp, true);
        return ['code' => $code, 'data' => $decoded ?? []];
    }

    /**
     * Create payment and return redirect URL
     */
    public function create_payment(int $user_id, float $amount, string $description = ''): array {
        $idempotence = uniqid('pay_', true);
        $return_url  = APP_URL . '/index.php?page=wallet&payment=success';
        $result = $this->request('POST', 'payments', [
            'amount'       => ['value' => number_format($amount, 2, '.', ''), 'currency' => 'RUB'],
            'capture'      => true,
            'confirmation' => ['type' => 'redirect', 'return_url' => $return_url],
            'description'  => $description ?: 'Пополнение баланса MineBet',
            'metadata'     => ['user_id' => $user_id, 'amount' => $amount],
        ], $idempotence);

        if ($result['code'] !== 200 || empty($result['data']['id'])) {
            return ['ok' => false, 'msg' => 'Ошибка создания платежа'];
        }

        $payment = $result['data'];
        // Save to DB
        db_execute(
            "INSERT INTO payments (user_id, yookassa_id, amount, status, description, metadata)
             VALUES (?,?,?,?,?,?)",
            [
                $user_id,
                $payment['id'],
                $amount,
                $payment['status'],
                $description,
                json_encode($payment['metadata'] ?? [])
            ]
        );

        return [
            'ok'           => true,
            'payment_id'   => $payment['id'],
            'redirect_url' => $payment['confirmation']['confirmation_url'],
        ];
    }

    /**
     * Handle webhook from YooKassa
     */
    public function handle_webhook(string $raw_body): bool {
        $event = json_decode($raw_body, true);
        if (!$event || $event['event'] !== 'payment.succeeded') return false;

        $payment_data = $event['object'] ?? [];
        $yookassa_id  = $payment_data['id'] ?? null;
        if (!$yookassa_id) return false;

        // Verify payment status via API
        $check = $this->request('GET', 'payments/' . $yookassa_id);
        if ($check['code'] !== 200 || $check['data']['status'] !== 'succeeded') return false;

        $payment = db_fetch("SELECT * FROM payments WHERE yookassa_id = ?", [$yookassa_id]);
        if (!$payment || $payment['status'] === 'completed') return true; // already processed

        $amount  = (float) $check['data']['amount']['value'];
        $user_id = (int)   $check['data']['metadata']['user_id'];

        get_db()->beginTransaction();
        try {
            db_execute("UPDATE payments SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE yookassa_id = ?", [$yookassa_id]);
            db_execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$amount, $user_id]);
            db_execute(
                "INSERT INTO transactions (user_id, type, amount, status, description, payment_id) VALUES (?,?,?,?,?,?)",
                [$user_id, 'deposit', $amount, 'completed', 'Пополнение через YooKassa', $yookassa_id]
            );
            get_db()->commit();
        } catch (\Exception $e) {
            get_db()->rollBack();
            return false;
        }
        return true;
    }

    /**
     * Check payment status manually
     */
    public function check_payment(string $yookassa_id): string {
        $result = $this->request('GET', 'payments/' . $yookassa_id);
        return $result['data']['status'] ?? 'unknown';
    }
}

// ─── WITHDRAWAL LOGIC ─────────────────────────────────────

function create_withdrawal(int $user_id, float $amount, string $card_number): array {
    if ($amount < MIN_WITHDRAW)
        return ['ok' => false, 'msg' => 'Минимальная сумма вывода: ' . MIN_WITHDRAW . '₽'];

    $user = db_fetch("SELECT balance FROM users WHERE id = ?", [$user_id]);
    if (!$user || $user['balance'] < $amount)
        return ['ok' => false, 'msg' => 'Недостаточно средств'];

    get_db()->beginTransaction();
    try {
        db_execute("UPDATE users SET balance = balance - ? WHERE id = ?", [$amount, $user_id]);
        db_execute(
            "INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?,?,?,?,?)",
            [$user_id, 'withdraw', $amount, 'pending', 'Вывод на карту ' . mask_card($card_number)]
        );
        get_db()->commit();
    } catch (\Exception $e) {
        get_db()->rollBack();
        return ['ok' => false, 'msg' => 'Ошибка обработки запроса'];
    }

    return ['ok' => true, 'msg' => 'Заявка на вывод ' . number_format($amount, 0, '.', ' ') . '₽ принята'];
}

function mask_card(string $card): string {
    $clean = preg_replace('/\D/', '', $card);
    if (strlen($clean) < 8) return '****';
    return substr($clean, 0, 4) . ' **** **** ' . substr($clean, -4);
}
