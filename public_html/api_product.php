<?php
// API endpoint to get product details for inline display
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID invalide']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Produit non trouvé']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM product_variations WHERE product_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$id]);
$variations = $stmt->fetchAll();

// Get colors
$stmt = $db->prepare("
    SELECT c.id, c.name, c.hex_code
    FROM product_colors pc
    JOIN colors c ON c.id = pc.color_id
    WHERE pc.product_id = ?
    ORDER BY c.name
");
$stmt->execute([$id]);
$colors = $stmt->fetchAll();

$product['image_url'] = $product['image'] ? UPLOAD_URL . $product['image'] : '';
$product['variations'] = $variations;
$product['colors'] = $colors;

echo json_encode($product);
