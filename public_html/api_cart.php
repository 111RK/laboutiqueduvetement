<?php
// API endpoint to manage cart
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $variation_id = (int)($_POST['variation_id'] ?? 0);
        $color_id = (int)($_POST['color_id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));

        $db = getDB();
        $stmt = $db->prepare("
            SELECT pv.*, p.title, p.image
            FROM product_variations pv
            JOIN products p ON p.id = pv.product_id
            WHERE pv.id = ? AND pv.is_active = 1 AND p.is_active = 1
        ");
        $stmt->execute([$variation_id]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode(['error' => 'Article non disponible']);
            exit;
        }

        // Get color name
        $color_name = '';
        if ($color_id) {
            $stmt = $db->prepare("SELECT name FROM colors WHERE id = ?");
            $stmt->execute([$color_id]);
            $c = $stmt->fetch();
            $color_name = $c ? $c['name'] : '';
        }

        $key = 'v_' . $variation_id . '_c_' . $color_id;
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$key] = [
                'variation_id' => $variation_id,
                'product_id' => $item['product_id'],
                'title' => $item['title'],
                'size_label' => $item['size_label'],
                'color_id' => $color_id,
                'color_name' => $color_name,
                'price' => $item['price'],
                'image' => $item['image'],
                'qty' => $qty,
            ];
        }
        break;

    case 'update':
        $key = $_POST['key'] ?? '';
        $qty = max(0, (int)($_POST['qty'] ?? 0));

        if ($qty === 0) {
            unset($_SESSION['cart'][$key]);
        } elseif (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] = $qty;
        }
        break;

    case 'remove':
        $key = $_POST['key'] ?? '';
        unset($_SESSION['cart'][$key]);
        break;

    case 'get':
    default:
        break;
}

$cart = $_SESSION['cart'];
$total = 0;
$count = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
    $count += $item['qty'];
}

echo json_encode([
    'items' => array_values($cart),
    'keys' => array_keys($cart),
    'count' => $count,
    'total' => $total,
    'total_formatted' => format_price($total),
]);
