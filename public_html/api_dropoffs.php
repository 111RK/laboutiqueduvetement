<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$service_id = $_GET['service_id'] ?? '';
$country = $_GET['country'] ?? 'FR';
$zipcode = $_GET['zipcode'] ?? '';

if (!$service_id || !$zipcode) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$result = packlink_request('GET', '/v1/dropoffs/' . urlencode($service_id) . '/' . urlencode($country) . '/' . urlencode($zipcode));

if (isset($result['error'])) {
    echo json_encode(['error' => $result['error']]);
    exit;
}

if ($result['code'] !== 200 || !is_array($result['body'])) {
    echo json_encode(['error' => 'Aucun point relais trouvé', 'points' => []]);
    exit;
}

$points = [];
foreach (array_slice($result['body'], 0, 20) as $p) {
    $points[] = [
        'id' => $p['id'] ?? '',
        'name' => $p['name'] ?? $p['commerce_name'] ?? '',
        'address' => $p['address'] ?? $p['street'] ?? '',
        'city' => $p['city'] ?? '',
        'zipcode' => $p['zip_code'] ?? $p['postal_code'] ?? $p['zipcode'] ?? '',
        'country' => $p['country'] ?? $country,
        'lat' => $p['lat'] ?? $p['latitude'] ?? null,
        'lon' => $p['lon'] ?? $p['longitude'] ?? null,
        'opening_hours' => $p['opening_hours'] ?? $p['openingHours'] ?? null,
    ];
}

echo json_encode(['points' => $points]);
