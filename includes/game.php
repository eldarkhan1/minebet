<?php
require_once __DIR__ . '/db.php';

// ─── PROVABLY FAIR CORE ────────────────────────────────────

function pf_generate_server_seed(): string {
    return bin2hex(random_bytes(32));
}

function pf_generate_client_seed(): string {
    return bin2hex(random_bytes(16));
}

function pf_hash_seed(string $server_seed, string $client_seed, int $nonce): string {
    return hash_hmac('sha256', $client_seed . ':' . $nonce, $server_seed);
}

function pf_bytes_from_hash(string $hash): array {
    $bytes = [];
    for ($i = 0; $i < strlen($hash); $i += 2) {
        $bytes[] = hexdec(substr($hash, $i, 2));
    }
    return $bytes;
}

function pf_float_from_hash(string $hash): float {
    // Get first 4 bytes → float 0..1
    $bytes = pf_bytes_from_hash($hash);
    $val = ($bytes[0] * 16777216 + $bytes[1] * 65536 + $bytes[2] * 256 + $bytes[3]);
    return $val / 4294967296;
}

// ─── MINES ────────────────────────────────────────────────

function mines_get_positions(string $hash, int $total = 25, int $mines_count = 5): array {
    $arr = range(0, $total - 1);
    // Fisher-Yates shuffle seeded by hash bytes
    $bytes = pf_bytes_from_hash($hash);
    $byte_idx = 0;
    for ($i = $total - 1; $i > 0; $i--) {
        // Get enough bytes for this swap
        $r = 0;
        $attempts = 0;
        do {
            if ($byte_idx >= count($bytes)) $byte_idx = 0;
            $r = ($r << 8) | $bytes[$byte_idx++];
            $attempts++;
        } while ($r < (256 - (256 % ($i + 1))) && $attempts < 4);
        $j = $r % ($i + 1);
        [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
    }
    return array_slice($arr, 0, $mines_count);
}

function mines_calc_multiplier(int $cells_revealed, int $total_cells, int $mines_count): float {
    if ($cells_revealed === 0) return 1.0;
    $safe_cells = $total_cells - $mines_count;
    $multiplier = 1.0;
    for ($i = 0; $i < $cells_revealed; $i++) {
        $multiplier *= ($total_cells - $mines_count - $i) / ($total_cells - $i);
    }
    return round((1.0 - HOUSE_EDGE) / $multiplier, 4);
}

// ─── HI-LO ────────────────────────────────────────────────

function hilo_get_number(string $hash, string $suffix = '_1'): int {
    $sub_hash = hash('sha256', $hash . $suffix);
    $val = hexdec(substr($sub_hash, 0, 8));
    return ($val % 100) + 1;
}

function hilo_multiplier(string $direction, int $current_number): float {
    if ($direction === 'higher') {
        $win_chance = (100 - $current_number) / 100;
    } else {
        $win_chance = ($current_number - 1) / 100;
    }
    if ($win_chance <= 0.01) return 99.0; // Cap at 99x for extreme numbers
    $mult = round((1.0 - HOUSE_EDGE) / $win_chance, 4);
    return min($mult, 99.0); // Hard cap
}

// ─── GAME SAVE ────────────────────────────────────────────

function save_game(
    int    $user_id,
    string $game_type,
    float  $bet,
    float  $payout,
    float  $multiplier,
    string $result,
    array  $game_data,
    string $server_seed,
    string $client_seed,
    int    $nonce
): int {
    $uid = db_execute(
        "INSERT INTO games (user_id, game_type, bet, payout, multiplier, result, game_data, server_seed, server_seed_hash, client_seed, nonce)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)",
        [
            $user_id, $game_type, $bet, $payout, $multiplier, $result,
            json_encode($game_data),
            $server_seed,
            hash('sha256', $server_seed),
            $client_seed,
            $nonce
        ]
    );
    // Update user stats
    db_execute(
        "UPDATE users SET
            total_wagered = total_wagered + ?,
            total_won     = total_won + ?,
            games_played  = games_played + 1
         WHERE id = ?",
        [$bet, $payout, $user_id]
    );
    return $uid;
}

// ─── VERIFY ───────────────────────────────────────────────

function verify_mines(string $server_seed, string $client_seed, int $nonce, int $mines_count): array {
    $hash = pf_hash_seed($server_seed, $client_seed, $nonce);
    $positions = mines_get_positions($hash, 25, $mines_count);
    sort($positions);
    return ['hash' => $hash, 'mine_positions' => $positions];
}

function verify_hilo(string $server_seed, string $client_seed, int $nonce): array {
    $hash = pf_hash_seed($server_seed, $client_seed, $nonce);
    $num1 = hilo_get_number($hash, '_1');
    $num2 = hilo_get_number($hash, '_2');
    return ['hash' => $hash, 'num1' => $num1, 'num2' => $num2];
}
