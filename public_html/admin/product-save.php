<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

csrf_verify();

$db = getDB();
$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0) ?: null;
$sort_order = (int)($_POST['sort_order'] ?? 0);
$sizes_data = $_POST['sizes'] ?? [];
$colors_data = $_POST['colors'] ?? [];

if (!$title) {
    flash('success', 'Erreur : titre requis.');
    header('Location: product-form.php' . ($id ? '?id=' . $id : ''));
    exit;
}

$slug = slugify($title);

$check = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
$check->execute([$slug, $id]);
if ($check->fetch()) {
    $slug .= '-' . substr(uniqid(), -4);
}

$image = '';
if ($id) {
    $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    $image = $existing['image'] ?? '';
}

if (!empty($_POST['remove_image']) && $image) {
    delete_product_image($image);
    $image = '';
}

if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $result = upload_product_image($_FILES['image']);
    if (isset($result['filename'])) {
        if ($image) delete_product_image($image);
        $image = $result['filename'];
    }
}

if ($id) {
    $stmt = $db->prepare("UPDATE products SET title=?, slug=?, description=?, category_id=?, image=?, sort_order=?, updated_at=datetime('now') WHERE id=?");
    $stmt->execute([$title, $slug, $description, $category_id, $image, $sort_order, $id]);
} else {
    $stmt = $db->prepare("INSERT INTO products (title, slug, description, category_id, image, sort_order) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$title, $slug, $description, $category_id, $image, $sort_order]);
    $id = $db->lastInsertId();
}

$db->prepare("DELETE FROM product_variations WHERE product_id = ?")->execute([$id]);

foreach ($sizes_data as $type => $type_sizes) {
    foreach ($type_sizes as $label => $data) {
        if (!empty($data['active'])) {
            $price = (float)($data['price'] ?? ($type === 'adult' ? 6.00 : 5.00));
            $stmt = $db->prepare("INSERT INTO product_variations (product_id, size_label, size_type, price) VALUES (?,?,?,?)");
            $stmt->execute([$id, $label, $type, $price]);
        }
    }
}

$db->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$id]);
foreach ($colors_data as $color_id) {
    $stmt = $db->prepare("INSERT INTO product_colors (product_id, color_id) VALUES (?,?)");
    $stmt->execute([$id, (int)$color_id]);
}

flash('success', $id ? 'Produit modifié avec succès.' : 'Produit ajouté avec succès.');
header('Location: products.php');
exit;
