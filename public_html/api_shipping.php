<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$zipcode = $_GET['zipcode'] ?? '';
$city = $_GET['city'] ?? '';
$country = $_GET['country'] ?? 'FR';

if (!$zipcode || !$city) {
    echo json_encode(['error' => 'Adresse incomplète']);
    exit;
}

$api_key = get_packlink_key();

if (empty($api_key)) {
    echo json_encode([
        'options' => [
            ['name' => 'Colissimo Domicile', 'price' => 4.99, 'transit_time' => '2-3 jours ouvrés', 'service_id' => '', 'pickup' => false],
            ['name' => 'Mondial Relay', 'price' => 3.49, 'transit_time' => '3-5 jours ouvrés', 'service_id' => '', 'pickup' => true],
            ['name' => 'Chronopost Express', 'price' => 8.99, 'transit_time' => '24h', 'service_id' => '', 'pickup' => false],
        ]
    ]);
    exit;
}

$base = get_packlink_base();

$params = http_build_query([
    'from[country]' => 'FR',
    'from[zip]' => '31210',
    'to[country]' => $country,
    'to[zip]' => $zipcode,
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
        'Authorization: ' . $api_key,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode([
        'options' => [
            ['name' => 'Colissimo Domicile', 'price' => 4.99, 'transit_time' => '2-3 jours ouvrés', 'service_id' => '', 'pickup' => false],
            ['name' => 'Mondial Relay', 'price' => 3.49, 'transit_time' => '3-5 jours ouvrés', 'service_id' => '', 'pickup' => true],
        ]
    ]);
    exit;
}

$services = json_decode($response, true);
$options = [];

// Carriers known to be pickup/relay services
$pickup_carriers = ['mondial relay', 'chrono2shop', 'chronopost shop', 'point relais', 'relay', 'shop2shop', 'pick up', 'pickup'];

foreach (array_slice($services, 0, 8) as $service) {
    $carrier = strtolower($service['carrier_name'] ?? '');
    $name = strtolower($service['name'] ?? '');
    $full = $carrier . ' ' . $name;

    // Detect pickup services
    $is_pickup = false;
    foreach ($pickup_carriers as $pk) {
        if (strpos($full, $pk) !== false) {
            $is_pickup = true;
            break;
        }
    }
    // Also check Packlink flags
    if (!empty($service['drop_off_available']) || !empty($service['dropoff_available'])) {
        $is_pickup = true;
    }
    if (isset($service['category']) && in_array($service['category'], ['pickup_point', 'drop_off'])) {
        $is_pickup = true;
    }

    $options[] = [
        'name' => $service['carrier_name'] . ' - ' . $service['name'],
        'price' => $service['price']['total_price'] ?? 0,
        'transit_time' => ($service['transit_hours'] ?? '') ? ceil($service['transit_hours'] / 24) . ' jours' : '',
        'service_id' => $service['id'] ?? '',
        'pickup' => $is_pickup,
    ];
}

echo json_encode(['options' => $options]);
