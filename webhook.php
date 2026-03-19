<?php
// Webhook endpoint for YooKassa payment notifications
// URL: https://yourdomain.com/webhook.php
// Set this URL in your YooKassa dashboard

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/payment.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$raw = file_get_contents('php://input');
if (empty($raw)) {
    http_response_code(400);
    exit('Empty body');
}

$yk = new YooKassa();
$ok = $yk->handle_webhook($raw);

http_response_code($ok ? 200 : 400);
echo $ok ? 'OK' : 'ERROR';
