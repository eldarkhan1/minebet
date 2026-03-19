<?php

function json_ok(array $data = []): never {
    header('Content-Type: application/json; charset=utf-8');
    // Replace any INF/NAN floats which would break json_encode
    array_walk_recursive($data, function(&$v) {
        if (is_float($v) && !is_finite($v)) $v = 0.0;
    });
    echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $msg, int $code = 200): never {
    if ($code !== 200) http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals(csrf_token(), $token)) {
        json_error('Неверный CSRF токен', 403);
    }
}

function fmt_money(mixed $n): string {
    return number_format((float)($n ?? 0), 2, '.', ' ');
}

function fmt_money_ru(mixed $n): string {
    return number_format((float)($n ?? 0), 0, '.', ' ') . '₽';
}

function time_ago(string $dt): string {
    $diff = time() - strtotime($dt);
    if ($diff < 60)    return 'только что';
    if ($diff < 3600)  return floor($diff / 60) . ' мин назад';
    if ($diff < 86400) return floor($diff / 3600) . ' ч назад';
    return floor($diff / 86400) . ' дн назад';
}

function esc(mixed $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_last_wins(int $limit = 15): array {
    return db_fetchAll(
        "SELECT g.id, g.game_type, g.bet, g.payout, g.multiplier, g.created_at,
                u.username, u.avatar_color
         FROM games g
         JOIN users u ON g.user_id = u.id
         WHERE g.result = 'win' AND g.payout > 0
         ORDER BY g.created_at DESC
         LIMIT ?",
        [$limit]
    );
}

function get_top_players(int $limit = 10): array {
    return db_fetchAll(
        "SELECT u.username, u.avatar_color, u.games_played,
                u.total_wagered, u.total_won,
                (u.total_won - u.total_wagered) as profit
         FROM users u
         WHERE u.games_played > 0
         ORDER BY profit DESC
         LIMIT ?",
        [$limit]
    );
}

function get_user_stats(int $user_id): array {
    $games = db_fetch(
        "SELECT
            COUNT(*) as total_games,
            SUM(CASE WHEN result='win' THEN 1 ELSE 0 END) as wins,
            SUM(bet) as total_wagered,
            SUM(payout) as total_won,
            MAX(payout) as biggest_win,
            MAX(multiplier) as biggest_mult
         FROM games WHERE user_id = ?",
        [$user_id]
    );
    $ref_count = (int) db_scalar("SELECT COUNT(*) FROM users WHERE referred_by = ?", [$user_id]);
    $ref_earned = (float) db_scalar(
        "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE user_id=? AND type='referral' AND status='completed'",
        [$user_id]
    );
    return array_merge($games ?? [], ['ref_count' => $ref_count, 'ref_earned' => $ref_earned]);
}

function get_user_transactions(int $user_id, int $limit = 20): array {
    return db_fetchAll(
        "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",
        [$user_id, $limit]
    );
}

function avatar_initials(string $username): string {
    return strtoupper(substr($username, 0, 2));
}
