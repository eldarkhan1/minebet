<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/game.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
auth_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── PUBLIC ENDPOINTS ─────────────────────────────────────
if ($action === 'last_wins') {
    json_ok(['wins' => get_last_wins(15)]);
}
if ($action === 'top_players') {
    json_ok(['top' => get_top_players(10)]);
}

// ─── REQUIRE AUTH ─────────────────────────────────────────
if (!is_logged_in()) {
    json_error('Необходима авторизация', 401);
}

// CSRF — принимаем из POST-поля или заголовка
$csrf_token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals(csrf_token(), $csrf_token)) {
    json_error('Неверный CSRF токен', 403);
}

$uid  = current_uid();
$user = current_user();
if (!$user) json_error('Пользователь не найден', 401);

// ─── GET BALANCE ──────────────────────────────────────────
if ($action === 'balance') {
    json_ok(['balance' => (float)$user['balance']]);
}

// ═══════════════════════════════════════════════════════════
// MINES GAME
// ═══════════════════════════════════════════════════════════

if ($action === 'mines_start') {
    $bet         = (float)($_POST['bet'] ?? 0);
    $mines_count = (int)($_POST['mines'] ?? 5);

    if ($bet < MIN_BET) json_error('Минимальная ставка: ' . MIN_BET . '₽');
    if ($bet > MAX_BET) json_error('Максимальная ставка: ' . MAX_BET . '₽');
    if ($mines_count < 1 || $mines_count > 24) json_error('Количество мин: от 1 до 24');
    if ($user['balance'] < $bet) json_error('Недостаточно средств');

    $server_seed = pf_generate_server_seed();
    $client_seed = pf_generate_client_seed();
    $nonce       = random_int(1, 999999);
    $hash        = pf_hash_seed($server_seed, $client_seed, $nonce);
    $mine_pos    = mines_get_positions($hash, 25, $mines_count);

    // Deduct bet immediately
    db_execute("UPDATE users SET balance = balance - ? WHERE id = ?", [$bet, $uid]);

    // Store game state in session
    $_SESSION['mines_game'] = [
        'uid'         => $uid,
        'bet'         => $bet,
        'mines_count' => $mines_count,
        'server_seed' => $server_seed,
        'client_seed' => $client_seed,
        'nonce'       => $nonce,
        'mine_pos'    => $mine_pos,
        'revealed'    => [],
        'active'      => true,
    ];

    $new_balance = (float)db_scalar("SELECT balance FROM users WHERE id=?", [$uid]);
    json_ok([
        'server_seed_hash' => hash('sha256', $server_seed),
        'client_seed'      => $client_seed,
        'nonce'            => $nonce,
        'balance'          => $new_balance,
    ]);
}

if ($action === 'mines_reveal') {
    $cell = (int)($_POST['cell'] ?? -1);
    $g    = $_SESSION['mines_game'] ?? null;

    if (!$g || !$g['active'] || $g['uid'] !== $uid)
        json_error('Нет активной игры');
    if ($cell < 0 || $cell > 24)
        json_error('Неверная ячейка');
    if (in_array($cell, $g['revealed']))
        json_error('Ячейка уже открыта');

    $is_mine = in_array($cell, $g['mine_pos']);

    if ($is_mine) {
        // LOSE
        $_SESSION['mines_game']['active'] = false;

        save_game($uid, 'mines', $g['bet'], 0, 0, 'lose',
            ['mine_positions' => $g['mine_pos'], 'revealed' => $g['revealed'], 'hit_cell' => $cell],
            $g['server_seed'], $g['client_seed'], $g['nonce']
        );

        $new_balance = (float)db_scalar("SELECT balance FROM users WHERE id=?", [$uid]);
        json_ok([
            'hit_mine'     => true,
            'mine_pos'     => $g['mine_pos'],
            'server_seed'  => $g['server_seed'],
            'balance'      => $new_balance,
        ]);
    }

    // SAFE CELL - update session directly
    $_SESSION['mines_game']['revealed'][] = $cell;
    $g = $_SESSION['mines_game']; // re-read updated session

    $safe_count  = 25 - $g['mines_count'];
    $revealed_n  = count($g['revealed']);
    $multiplier  = mines_calc_multiplier($revealed_n, 25, $g['mines_count']);
    $potential   = round($g['bet'] * $multiplier, 2);
    $all_safe    = ($revealed_n >= $safe_count);

    if ($all_safe) {
        // Auto-cashout: all safe cells revealed
        $_SESSION['mines_game']['active'] = false;
        db_execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$potential, $uid]);
        save_game($uid, 'mines', $g['bet'], $potential, $multiplier, 'win',
            ['mine_positions' => $g['mine_pos'], 'revealed' => $g['revealed']],
            $g['server_seed'], $g['client_seed'], $g['nonce']
        );
    }

    $new_balance = (float)db_scalar("SELECT balance FROM users WHERE id=?", [$uid]);
    json_ok([
        'hit_mine'   => false,
        'revealed'   => $g['revealed'],
        'multiplier' => $multiplier,
        'potential'  => $potential,
        'all_safe'   => $all_safe,
        'balance'    => $new_balance,
        'server_seed'=> $all_safe ? $g['server_seed'] : null,
    ]);
}

if ($action === 'mines_cashout') {
    $g = $_SESSION['mines_game'] ?? null;

    if (!$g || !$g['active'] || $g['uid'] !== $uid)
        json_error('Нет активной игры');
    if (empty($g['revealed']))
        json_error('Нужно открыть хотя бы одну ячейку');

    $revealed_n = count($g['revealed']);
    $multiplier = mines_calc_multiplier($revealed_n, 25, $g['mines_count']);
    $payout     = round($g['bet'] * $multiplier, 2);

    $_SESSION['mines_game']['active'] = false;

    db_execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$payout, $uid]);
    save_game($uid, 'mines', $g['bet'], $payout, $multiplier, 'win',
        ['mine_positions' => $g['mine_pos'], 'revealed' => $g['revealed']],
        $g['server_seed'], $g['client_seed'], $g['nonce']
    );

    $new_balance = (float)db_scalar("SELECT balance FROM users WHERE id=?", [$uid]);
    json_ok([
        'payout'      => $payout,
        'multiplier'  => $multiplier,
        'mine_pos'    => $g['mine_pos'],
        'server_seed' => $g['server_seed'],
        'balance'     => $new_balance,
    ]);
}

// ═══════════════════════════════════════════════════════════
// HI-LO GAME
// ═══════════════════════════════════════════════════════════

if ($action === 'hilo_init') {
    // Just generate a number for display (no bet yet)
    $server_seed = pf_generate_server_seed();
    $client_seed = pf_generate_client_seed();
    $nonce       = random_int(1, 999999);
    $hash        = pf_hash_seed($server_seed, $client_seed, $nonce);
    $num1        = hilo_get_number($hash, '_1');

    $_SESSION['hilo_game'] = [
        'uid'         => $uid,
        'server_seed' => $server_seed,
        'client_seed' => $client_seed,
        'nonce'       => $nonce,
        'num1'        => $num1,
        'active'      => true,
    ];

    json_ok([
        'num1'             => $num1,
        'server_seed_hash' => hash('sha256', $server_seed),
        'client_seed'      => $client_seed,
        'nonce'            => $nonce,
        'mult_higher'      => hilo_multiplier('higher', $num1),
        'mult_lower'       => hilo_multiplier('lower', $num1),
    ]);
}

if ($action === 'hilo_play') {
    $bet   = (float)($_POST['bet'] ?? 0);
    $guess = $_POST['guess'] ?? '';
    $g     = $_SESSION['hilo_game'] ?? null;

    if ($bet < MIN_BET) json_error('Минимальная ставка: ' . MIN_BET . '₽');
    if ($bet > MAX_BET) json_error('Максимальная ставка: ' . MAX_BET . '₽');
    if (!in_array($guess, ['higher', 'lower'])) json_error('Неверный выбор');
    if ($user['balance'] < $bet) json_error('Недостаточно средств');
    if (!$g || !$g['active'] || $g['uid'] !== $uid) json_error('Начните игру заново');

    $hash  = pf_hash_seed($g['server_seed'], $g['client_seed'], $g['nonce']);
    $num1  = $g['num1'];
    $num2  = hilo_get_number($hash, '_2');

    $win = ($guess === 'higher' && $num2 > $num1) ||
           ($guess === 'lower'  && $num2 < $num1);

    $multiplier  = hilo_multiplier($guess, $num1);
    $payout      = $win ? round($bet * $multiplier, 2) : 0;
    $profit      = $payout - $bet;

    db_execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$profit, $uid]);
    save_game($uid, 'hilo', $bet, $payout, $win ? $multiplier : 0,
        $win ? 'win' : 'lose',
        ['num1' => $num1, 'num2' => $num2, 'guess' => $guess],
        $g['server_seed'], $g['client_seed'], $g['nonce']
    );

    // Prepare next round
    $new_server   = pf_generate_server_seed();
    $new_client   = pf_generate_client_seed();
    $new_nonce    = random_int(1, 999999);
    $new_hash     = pf_hash_seed($new_server, $new_client, $new_nonce);
    $next_num     = hilo_get_number($new_hash, '_1');

    $_SESSION['hilo_game'] = [
        'uid'         => $uid,
        'server_seed' => $new_server,
        'client_seed' => $new_client,
        'nonce'       => $new_nonce,
        'num1'        => $next_num,
        'active'      => true,
    ];

    $new_balance = (float)db_scalar("SELECT balance FROM users WHERE id=?", [$uid]);
    json_ok([
        'win'              => $win,
        'num1'             => $num1,
        'num2'             => $num2,
        'multiplier'       => $multiplier,
        'payout'           => $payout,
        'server_seed'      => $g['server_seed'],
        'balance'          => $new_balance,
        'next_num'         => $next_num,
        'next_seed_hash'   => hash('sha256', $new_server),
        'next_client_seed' => $new_client,
        'next_nonce'       => $new_nonce,
        'mult_higher'      => hilo_multiplier('higher', $next_num),
        'mult_lower'       => hilo_multiplier('lower', $next_num),
    ]);
}

json_error('Неизвестное действие');
