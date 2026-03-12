<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['order_status'] ?? '';
$allowed = ['new', 'confirmed', 'shipped', 'delivered'];

if ($id && in_array($status, $allowed)) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE orders SET order_status = ?, updated_at = datetime('now') WHERE id = ?");
    $stmt->execute([$status, $id]);
}

header('Location: orders.php');
exit;
