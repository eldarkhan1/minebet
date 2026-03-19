<?php
// ─── APP CONFIG ────────────────────────────────────────────
define('APP_NAME',    'MineBet');
define('APP_URL',     'http://localhost'); // Change to your domain
define('APP_VERSION', '1.0.0');

// ─── DATABASE ──────────────────────────────────────────────
define('DB_FILE', __DIR__ . '/../data/minebet.db');

// ─── YOOKASSA ──────────────────────────────────────────────
define('YOOKASSA_SHOP_ID',  'YOUR_SHOP_ID');   // Replace with real shop ID
define('YOOKASSA_SECRET',   'YOUR_SECRET_KEY'); // Replace with real secret

// ─── GAME SETTINGS ─────────────────────────────────────────
define('MIN_BET',       1.0);
define('MAX_BET',       50000.0);
define('MIN_DEPOSIT',   100.0);
define('MIN_WITHDRAW',  500.0);
define('HOUSE_EDGE',    0.03);  // 3% house edge
define('START_BALANCE', 0.0);   // Starting balance for new users

// ─── REFERRAL ──────────────────────────────────────────────
define('REF_BONUS_REFERRER', 100.0); // Bonus to referrer
define('REF_BONUS_NEW_USER', 50.0);  // Bonus to new user who used ref code

// ─── TIMEZONE ──────────────────────────────────────────────
date_default_timezone_set('Europe/Moscow');
