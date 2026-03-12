<?php
session_start();

// Database
define('DB_PATH', __DIR__ . '/../../data/boutique.sqlite');

// Site
define('SITE_NAME', 'La Boutique du Vêtement');
define('SITE_URL', '');

// Uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', 'uploads/products/');

// Admin password (set during install)
define('ADMIN_PASSWORD_HASH', '$2y$10$defaulthashchangethisduringinstall');

// PayPlug API
define('PAYPLUG_SECRET_KEY', ''); // À remplir avec votre clé secrète PayPlug
define('PAYPLUG_PUBLIC_KEY', ''); // À remplir avec votre clé publique PayPlug
define('PAYPLUG_TEST_MODE', true); // true = mode test, false = production

// Packlink API
define('PACKLINK_API_KEY', ''); // À remplir avec votre clé API Packlink
define('PACKLINK_TEST_MODE', true);

// Predefined sizes
define('SIZES', json_encode([
    'adult' => [
        ['label' => 'XS',  'price' => 6.00],
        ['label' => 'S',   'price' => 6.00],
        ['label' => 'M',   'price' => 6.00],
        ['label' => 'L',   'price' => 6.00],
        ['label' => 'XL',  'price' => 6.00],
        ['label' => 'XXL', 'price' => 6.00],
    ],
    'child' => [
        ['label' => '3/4 ans',   'price' => 5.00],
        ['label' => '5/6 ans',   'price' => 5.00],
        ['label' => '7/8 ans',   'price' => 5.00],
        ['label' => '9/11 ans',  'price' => 5.00],
        ['label' => '12/14 ans', 'price' => 5.00],
    ],
]));

// CSRF protection
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Token CSRF invalide.');
    }
}
