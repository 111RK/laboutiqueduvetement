<?php
// Packlink API integration for shipping options
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$zipcode = $_GET['zipcode'] ?? '';
$city = $_GET['city'] ?? '';

if (!$zipcode || !$city) {
    echo json_encode(['error' => 'Adresse incomplète']);
    exit;
}

// If no API key configured, return default options
if (empty(PACKLINK_API_KEY)) {
    echo json_encode([
        'options' => [
            ['name' => 'Colissimo Domicile', 'price' => 4.99, 'transit_time' => '2-3 jours ouvrés'],
            ['name' => 'Mondial Relay', 'price' => 3.49, 'transit_time' => '3-5 jours ouvrés'],
            ['name' => 'Chronopost Express', 'price' => 8.99, 'transit_time' => '24h'],
        ]
    ]);
    exit;
}

// Packlink PRO API - Search shipping services
$api_base = PACKLINK_TEST_MODE
    ? 'https://apisandbox.packlink.com'
    : 'https://api.packlink.com';

$params = http_build_query([
    'from[country]' => 'FR',
    'from[zip]' => '75001', // Warehouse zip - to configure
    'to[country]' => 'FR',
    'to[zip]' => $zipcode,
    'packages[0][width]' => 30,
    'packages[0][height]' => 10,
    'packages[0][length]' => 40,
    'packages[0][weight]' => 0.5,
    'sortBy' => 'totalPrice',
    'source' => 'PRO',
]);

$ch = curl_init($api_base . '/v1/services?' . $params);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: ' . PACKLINK_API_KEY,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    // Fallback to default options
    echo json_encode([
        'options' => [
            ['name' => 'Colissimo Domicile', 'price' => 4.99, 'transit_time' => '2-3 jours ouvrés'],
            ['name' => 'Mondial Relay', 'price' => 3.49, 'transit_time' => '3-5 jours ouvrés'],
        ]
    ]);
    exit;
}

$services = json_decode($response, true);
$options = [];

foreach (array_slice($services, 0, 5) as $service) {
    $options[] = [
        'name' => $service['carrier_name'] . ' - ' . $service['name'],
        'price' => $service['price']['total_price'] ?? 0,
        'transit_time' => ($service['transit_hours'] ?? '') ? ceil($service['transit_hours'] / 24) . ' jours' : '',
        'service_id' => $service['id'] ?? '',
    ];
}

echo json_encode(['options' => $options]);
