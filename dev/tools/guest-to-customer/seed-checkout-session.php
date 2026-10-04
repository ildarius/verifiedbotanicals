<?php
// Writes what checkout would have stored in a browser session after placing the order, so the success
// page can be tested headless. usage: php seed-checkout-session.php <PHPSESSID> <order entity id> <mark-new-account 0|1>
[$_, $sid, $orderId, $mark] = $argv;
$env = include dirname(__DIR__, 3) . '/app/etc/env.php';
$c = $env['db']['connection']['default'];
$pdo = new PDO("mysql:host={$c['host']};dbname={$c['dbname']}", $c['username'], $c['password']);
$o = $pdo->query('SELECT entity_id, increment_id, quote_id, customer_id, status FROM sales_order WHERE entity_id=' . (int)$orderId)->fetch(PDO::FETCH_ASSOC);
session_save_path($env['session']['save_path']);
session_id($sid);
session_start();
$_SESSION['checkout']['last_quote_id'] = $o['quote_id'];
$_SESSION['checkout']['last_success_quote_id'] = $o['quote_id'];
$_SESSION['checkout']['last_order_id'] = $o['entity_id'];
$_SESSION['checkout']['last_real_order_id'] = $o['increment_id'];
$_SESSION['checkout']['last_order_status'] = $o['status'];
if ($mark) {
    $_SESSION['checkout']['local_guest_to_customer_new_account'] = ['order_id' => (int)$o['entity_id'], 'customer_id' => (int)$o['customer_id'], 'created_at' => time()];
}
session_write_close();
echo json_encode($o), "\n";
