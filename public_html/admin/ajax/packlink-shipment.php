<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$order_id = (int)($_POST['order_id'] ?? 0);
if (!$order_id) {
    echo json_encode(['error' => 'ID commande manquant']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['error' => 'Commande introuvable']);
    exit;
}

$key = get_packlink_key();
if (!$key) {
    echo json_encode(['error' => 'Clé API Packlink non configurée']);
    exit;
}

// Split customer name into first/last
$name_parts = explode(' ', $order['customer_name'], 2);
$first_name = $name_parts[0];
$last_name = $name_parts[1] ?? $name_parts[0];

// Sender info (your shop)
$from = [
    'first_name' => 'Thomas',
    'last_name' => 'Albert',
    'company' => 'La Boutique du Vêtement',
    'street1' => '13 Rue de l\'Arbizon',
    'zip_code' => '31210',
    'city' => 'Montréjeau',
    'country' => 'FR',
    'phone' => '0695872851',
    'email' => 'contact@laboutiqueduvetement.fr',
];

// Recipient
$to_address = $order['relay_point_address'] ?: $order['customer_address'];
$to = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'street1' => $to_address,
    'zip_code' => $order['customer_zipcode'],
    'city' => $order['customer_city'],
    'country' => $order['customer_country'] ?: 'FR',
    'phone' => $order['customer_phone'] ?: '',
    'email' => $order['customer_email'],
];

// Parse items for weight/value
$items = json_decode($order['items_json'], true) ?: [];
$total_qty = 0;
foreach ($items as $item) {
    $total_qty += $item['qty'] ?? 1;
}

// Build items description
$content_desc = [];
foreach ($items as $item) {
    $content_desc[] = ($item['qty'] ?? 1) . 'x ' . ($item['title'] ?? 'Article');
}

$shipment_data = [
    'from' => $from,
    'to' => $to,
    'packages' => [
        [
            'width' => 30,
            'height' => max(10, $total_qty * 5),
            'length' => 40,
            'weight' => max(0.5, $total_qty * 0.3),
        ]
    ],
    'content' => implode(', ', $content_desc),
    'contentvalue' => (float)$order['subtotal'],
    'content_second_hand' => false,
    'source' => 'PRO',
    'order_reference' => $order['order_ref'],
];

// Add service_id - required for Packlink to select the carrier
if (!empty($order['shipping_service_id'])) {
    $shipment_data['service_id'] = (int)$order['shipping_service_id'];
} else {
    // Try to find matching service by name from cached carriers
    $cached_raw = $db->query("SELECT value FROM settings WHERE key = 'cached_carriers'")->fetchColumn();
    $cached = $cached_raw ? json_decode($cached_raw, true) : [];
    $method = strtolower($order['shipping_method'] ?? '');
    foreach ($cached as $c) {
        $full = strtolower($c['carrier_name'] . ' - ' . $c['name']);
        if ($method && ($full === $method || strpos($full, $method) !== false || strpos($method, strtolower($c['carrier_name'])) !== false)) {
            $shipment_data['service_id'] = (int)$c['id'];
            break;
        }
    }
}

// Add dropoff point if relay
if (!empty($order['relay_point_id'])) {
    $shipment_data['dropoff_point_id'] = $order['relay_point_id'];
}

$result = packlink_request('POST', '/v1/shipments', $shipment_data);

if (isset($result['error'])) {
    echo json_encode(['error' => $result['error']]);
    exit;
}

if ($result['code'] >= 200 && $result['code'] < 300 && !empty($result['body']['reference'])) {
    $reference = $result['body']['reference'];

    // Save Packlink reference to order
    $stmt = $db->prepare("UPDATE orders SET shipping_tracking = ?, updated_at = datetime('now') WHERE id = ?");
    $stmt->execute(['PL-' . $reference, $order_id]);

    // Always use production Packlink Pro URL (key is live)
    $url = 'https://pro.packlink.com/private/shipments/' . $reference;

    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'url' => $url,
    ]);
} else {
    $error_msg = 'Erreur Packlink';
    if (!empty($result['body']['message'])) {
        $error_msg .= ': ' . $result['body']['message'];
    } elseif (!empty($result['body']['messages'])) {
        $msgs = $result['body']['messages'];
        if (is_array($msgs)) {
            $flat = [];
            array_walk_recursive($msgs, function($v) use (&$flat) { $flat[] = $v; });
            $error_msg .= ': ' . implode(', ', $flat);
        } else {
            $error_msg .= ': ' . $msgs;
        }
    }
    echo json_encode(['error' => $error_msg, 'debug' => $result['body']]);
}
