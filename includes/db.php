<?php
require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dir = dirname(DB_FILE);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
    init_db($pdo);
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        username     TEXT    UNIQUE NOT NULL,
        email        TEXT    UNIQUE NOT NULL,
        password     TEXT    NOT NULL,
        balance      REAL    DEFAULT 0.0,
        ref_code     TEXT    UNIQUE NOT NULL,
        referred_by  INTEGER DEFAULT NULL,
        avatar_color TEXT    DEFAULT '#00e676',
        total_wagered REAL   DEFAULT 0.0,
        total_won     REAL   DEFAULT 0.0,
        games_played  INTEGER DEFAULT 0,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login   DATETIME DEFAULT NULL,
        FOREIGN KEY (referred_by) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS games (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id      INTEGER NOT NULL,
        game_type    TEXT    NOT NULL,   -- 'mines' | 'hilo'
        bet          REAL    NOT NULL,
        payout       REAL    NOT NULL DEFAULT 0,
        multiplier   REAL    NOT NULL DEFAULT 0,
        result       TEXT    NOT NULL,   -- 'win' | 'lose'
        game_data    TEXT    DEFAULT '{}',
        server_seed  TEXT    NOT NULL,
        server_seed_hash TEXT NOT NULL,
        client_seed  TEXT    NOT NULL,
        nonce        INTEGER NOT NULL,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS transactions (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id      INTEGER NOT NULL,
        type         TEXT    NOT NULL,   -- 'deposit' | 'withdraw' | 'game_win' | 'game_loss' | 'referral'
        amount       REAL    NOT NULL,
        status       TEXT    DEFAULT 'pending', -- 'pending' | 'completed' | 'failed' | 'cancelled'
        description  TEXT    DEFAULT '',
        payment_id   TEXT    DEFAULT NULL,  -- YooKassa payment ID
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE TABLE IF NOT EXISTS payments (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id      INTEGER NOT NULL,
        yookassa_id  TEXT    UNIQUE NOT NULL,
        amount       REAL    NOT NULL,
        status       TEXT    DEFAULT 'pending',
        description  TEXT    DEFAULT '',
        metadata     TEXT    DEFAULT '{}',
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );

    CREATE INDEX IF NOT EXISTS idx_games_user    ON games(user_id);
    CREATE INDEX IF NOT EXISTS idx_games_type    ON games(game_type);
    CREATE INDEX IF NOT EXISTS idx_games_created ON games(created_at DESC);
    CREATE INDEX IF NOT EXISTS idx_tx_user       ON transactions(user_id);
    CREATE INDEX IF NOT EXISTS idx_payments_user ON payments(user_id);
    ");
}

// ─── QUERY HELPERS ─────────────────────────────────────────
function db_fetch(string $sql, array $params = []): ?array {
    $s = get_db()->prepare($sql);
    $s->execute($params);
    return $s->fetch() ?: null;
}

function db_fetchAll(string $sql, array $params = []): array {
    $s = get_db()->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

function db_execute(string $sql, array $params = []): int {
    $s = get_db()->prepare($sql);
    $s->execute($params);
    return (int) get_db()->lastInsertId();
}

function db_scalar(string $sql, array $params = []): mixed {
    $s = get_db()->prepare($sql);
    $s->execute($params);
    $row = $s->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : null;
}
