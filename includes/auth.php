<?php
require_once __DIR__ . '/db.php';

function auth_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in(): bool {
    auth_start();
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return db_fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

function current_uid(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function require_auth(): void {
    if (!is_logged_in()) {
        if (is_ajax()) {
            json_error('Необходима авторизация', 401);
        }
        header('Location: /index.php?page=login');
        exit;
    }
}

function is_ajax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function login_user(int $uid): void {
    auth_start();
    // session_regenerate_id can fail on some Windows setups — suppress error
    @session_regenerate_id(true);
    $_SESSION['user_id'] = $uid;
    db_execute("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?", [$uid]);
}

function logout_user(): void {
    auth_start();
    session_destroy();
}

function generate_ref_code(): string {
    do {
        $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    } while (db_fetch("SELECT id FROM users WHERE ref_code = ?", [$code]));
    return $code;
}

function register_user(string $username, string $email, string $password, ?string $ref_code = null): array {
    // Validation
    if (strlen($username) < 3 || strlen($username) > 20)
        return ['ok' => false, 'msg' => 'Имя от 3 до 20 символов'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))
        return ['ok' => false, 'msg' => 'Имя: только буквы, цифры и _'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['ok' => false, 'msg' => 'Некорректный email'];
    if (strlen($password) < 6)
        return ['ok' => false, 'msg' => 'Пароль минимум 6 символов'];
    if (db_fetch("SELECT id FROM users WHERE username = ?", [$username]))
        return ['ok' => false, 'msg' => 'Имя пользователя уже занято'];
    if (db_fetch("SELECT id FROM users WHERE email = ?", [$email]))
        return ['ok' => false, 'msg' => 'Email уже зарегистрирован'];

    $referred_by = null;
    $bonus = START_BALANCE;

    if ($ref_code) {
        $referrer = db_fetch("SELECT id FROM users WHERE ref_code = ?", [$ref_code]);
        if ($referrer) {
            $referred_by = $referrer['id'];
            $bonus += REF_BONUS_NEW_USER;
        }
    }

    $colors = ['#00e676','#00bcd4','#ff6b35','#a259ff','#ff1744','#ffd740'];
    $avatar_color = $colors[array_rand($colors)];

    $uid = db_execute(
        "INSERT INTO users (username, email, password, balance, ref_code, referred_by, avatar_color) VALUES (?,?,?,?,?,?,?)",
        [$username, $email, password_hash($password, PASSWORD_DEFAULT), $bonus, generate_ref_code(), $referred_by, $avatar_color]
    );

    // Give referral bonus to referrer
    if ($referred_by) {
        db_execute("UPDATE users SET balance = balance + ? WHERE id = ?", [REF_BONUS_REFERRER, $referred_by]);
        db_execute("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?,?,?,?,?)",
            [$referred_by, 'referral', REF_BONUS_REFERRER, 'completed', 'Реферальный бонус за пользователя ' . $username]);
        db_execute("INSERT INTO transactions (user_id, type, amount, status, description) VALUES (?,?,?,?,?)",
            [$uid, 'referral', $bonus, 'completed', 'Бонус за регистрацию по реферальной ссылке']);
    }

    return ['ok' => true, 'uid' => $uid];
}

function login_check(string $login, string $password): array {
    $user = db_fetch(
        "SELECT * FROM users WHERE username = ? OR email = ?",
        [$login, $login]
    );
    if (!$user || !password_verify($password, $user['password']))
        return ['ok' => false, 'msg' => 'Неверный логин или пароль'];
    return ['ok' => true, 'uid' => $user['id']];
}
