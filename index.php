<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/game.php';
require_once __DIR__ . '/includes/payment.php';

auth_start();

$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home','login','register','logout','mines','hilo','profile','wallet','top','fair','ref'];
if (!in_array($page, $allowed_pages)) $page = 'home';

// ── Shared template variables ─────────────────────────────
$error   = '';
$success = '';

// ═════════════════════════════════════════════════════════
// ALL POST / REDIRECT LOGIC — must happen before ANY output
// ═════════════════════════════════════════════════════════

// ── LOGOUT ───────────────────────────────────────────────
if ($page === 'logout') {
    logout_user();
    header('Location: /index.php?page=login');
    exit;
}

// ── LOGIN ────────────────────────────────────────────────
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $result = login_check(
        trim($_POST['login'] ?? ''),
        $_POST['password'] ?? ''
    );
    if ($result['ok']) {
        login_user($result['uid']);
        $redirect = preg_replace('/[^a-z]/', '', $_GET['redirect'] ?? 'home');
        if (!in_array($redirect, $allowed_pages)) $redirect = 'home';
        header('Location: /index.php?page=' . $redirect);
        exit;
    }
    $error = $result['msg'];
}

// ── REGISTER ─────────────────────────────────────────────
if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $result = register_user(
        trim($_POST['username'] ?? ''),
        trim($_POST['email']    ?? ''),
        $_POST['password']      ?? '',
        trim($_POST['ref_code'] ?? '')
    );
    if ($result['ok']) {
        login_user($result['uid']);
        header('Location: /index.php?page=home');
        exit;
    }
    $error = $result['msg'];
}

// ── WALLET: deposit / withdraw ───────────────────────────
if ($page === 'wallet' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) { header('Location: /index.php?page=login'); exit; }
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'deposit') {
        $amount = floatval($_POST['amount'] ?? 0);
        if ($amount < MIN_DEPOSIT) {
            $error = 'Минимальная сумма пополнения: ' . MIN_DEPOSIT . '₽';
        } else {
            $yk     = new YooKassa();
            $uid    = current_uid();
            $result = $yk->create_payment($uid, $amount, 'Пополнение MineBet');
            if ($result['ok']) {
                header('Location: ' . $result['redirect_url']);
                exit;
            }
            $error = $result['msg'];
        }
    }

    if ($action === 'withdraw') {
        $amount = floatval($_POST['amount'] ?? 0);
        $card   = trim($_POST['card'] ?? '');
        $result = create_withdrawal(current_uid(), $amount, $card);
        if ($result['ok']) {
            $success = $result['msg'];
        } else {
            $error = $result['msg'];
        }
    }
}

// ── PROFILE: change avatar color ─────────────────────────
if ($page === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_color'])) {
    if (!is_logged_in()) { header('Location: /index.php?page=login'); exit; }
    csrf_check();
    $allowed_colors = ['#00e676','#00bcd4','#ff6b35','#a259ff','#ff1744','#ffd740','#40c4ff','#f06292'];
    $color = $_POST['color'] ?? '#00e676';
    if (in_array($color, $allowed_colors)) {
        db_execute("UPDATE users SET avatar_color = ? WHERE id = ?", [$color, current_uid()]);
    }
    header('Location: /index.php?page=profile');
    exit;
}

// ── PAYMENT RETURN ────────────────────────────────────────
if ($page === 'wallet' && isset($_GET['payment']) && $_GET['payment'] === 'success') {
    $success = '✅ Платёж обработан. Баланс будет зачислен через несколько секунд.';
}

// ═════════════════════════════════════════════════════════
// ACCESS CONTROL (after POST handling, before output)
// ═════════════════════════════════════════════════════════
$protected = ['mines','hilo','profile','wallet','ref'];
if (in_array($page, $protected) && !is_logged_in()) {
    header('Location: /index.php?page=login&redirect=' . $page);
    exit;
}
if (in_array($page, ['login','register']) && is_logged_in()) {
    header('Location: /index.php?page=home');
    exit;
}

// ═════════════════════════════════════════════════════════
// LOAD USER — after all redirects, before output
// ═════════════════════════════════════════════════════════
$user = current_user();
if ($user === null && in_array($page, $protected)) {
    logout_user();
    header('Location: /index.php?page=login');
    exit;
}
if ($user === null) {
    $user = [
        'id'           => 0,
        'username'     => '',
        'email'        => '',
        'balance'      => 0.0,
        'ref_code'     => '',
        'avatar_color' => '#00e676',
        'created_at'   => date('Y-m-d H:i:s'),
    ];
}

// ═════════════════════════════════════════════════════════
// OUTPUT — HTML starts here only
// ═════════════════════════════════════════════════════════
include __DIR__ . '/includes/layout_head.php';

switch ($page) {
    case 'home':     include __DIR__ . '/pages/home.php';     break;
    case 'login':    include __DIR__ . '/pages/login.php';    break;
    case 'register': include __DIR__ . '/pages/register.php'; break;
    case 'mines':    include __DIR__ . '/pages/mines.php';    break;
    case 'hilo':     include __DIR__ . '/pages/hilo.php';     break;
    case 'profile':  include __DIR__ . '/pages/profile.php';  break;
    case 'wallet':   include __DIR__ . '/pages/wallet.php';   break;
    case 'top':      include __DIR__ . '/pages/top.php';      break;
    case 'fair':     include __DIR__ . '/pages/fair.php';     break;
    case 'ref':      include __DIR__ . '/pages/referral.php'; break;
}

include __DIR__ . '/includes/layout_foot.php';
