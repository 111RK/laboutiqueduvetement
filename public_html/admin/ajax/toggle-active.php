<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("UPDATE products SET is_active = NOT is_active, updated_at = datetime('now') WHERE id = ?");
$stmt->execute([$id]);

$stmt = $db->prepare("SELECT is_active FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

echo json_encode(['is_active' => (bool)$product['is_active']]);
