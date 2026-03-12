<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

$key = get_packlink_key();
if (!$key) {
    echo json_encode(['error' => 'Clé API Packlink non configurée. Ajoutez-la dans les paramètres.']);
    exit;
}

$base = get_packlink_base();

// Fetch services with a broad FR -> FR query to get all available carriers
$params = http_build_query([
    'from[country]' => 'FR',
    'from[zip]' => '31210',
    'to[country]' => 'FR',
    'to[zip]' => '75001',
    'packages[0][width]' => 30,
    'packages[0][height]' => 10,
    'packages[0][length]' => 40,
    'packages[0][weight]' => 0.5,
    'sortBy' => 'totalPrice',
    'source' => 'PRO',
]);

$ch = curl_init($base . '/v1/services?' . $params);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . $key,
        'Content-Type: application/json',
    ],
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['error' => 'Erreur Packlink (HTTP ' . $http_code . '). Vérifiez votre clé API.']);
    exit;
}

$services = json_decode($response, true);
if (!is_array($services)) {
    echo json_encode(['error' => 'Réponse Packlink invalide.']);
    exit;
}

$carriers = [];
foreach ($services as $s) {
    $is_pickup = !empty($s['dropoff']) || !empty($s['delivery_to_parcelshop']);
    $carriers[] = [
        'id' => (int)$s['id'],
        'name' => trim($s['name'] ?? ''),
        'carrier_name' => $s['carrier_name'] ?? '',
        'price' => $s['price']['total_price'] ?? $s['base_price'] ?? 0,
        'transit_time' => ($s['transit_hours'] ?? '') ? ceil($s['transit_hours'] / 24) . ' jours' : ($s['transit_time'] ?? ''),
        'pickup' => $is_pickup,
    ];
}

// Cache carriers in DB for display on page reload
$db = getDB();
$stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
$stmt->execute(['cached_carriers', json_encode($carriers)]);

// Get currently enabled carriers
$enabled_raw = $db->query("SELECT value FROM settings WHERE key = 'enabled_carriers'")->fetchColumn();
$enabled = $enabled_raw ? json_decode($enabled_raw, true) : [];

echo json_encode([
    'carriers' => $carriers,
    'enabled' => $enabled,
]);
