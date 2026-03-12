<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    $db = getDB();

    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        if ($product['image']) {
            delete_product_image($product['image']);
        }
        $db->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM product_variations WHERE product_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
}

flash('success', 'Produit supprimé.');
header('Location: products.php');
exit;
