<?php
// PayPlug webhook - called by PayPlug when payment status changes
require_once __DIR__ . '/includes/db.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    exit;
}

$payment_id = $data['id'];
$is_paid = $data['is_paid'] ?? false;
$failure = $data['failure'] ?? null;

$db = getDB();

$stmt = $db->prepare("SELECT id FROM orders WHERE payment_id = ?");
$stmt->execute([$payment_id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit;
}

if ($is_paid) {
    $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'confirmed', updated_at = datetime('now') WHERE id = ?");
    $stmt->execute([$order['id']]);
} elseif ($failure) {
    $stmt = $db->prepare("UPDATE orders SET payment_status = 'failed', updated_at = datetime('now') WHERE id = ?");
    $stmt->execute([$order['id']]);
}

http_response_code(200);
echo 'OK';
