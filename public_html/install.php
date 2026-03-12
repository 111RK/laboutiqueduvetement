<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$installed = false;

$db = getDB();

$tables = [
    "CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        sort_order INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now'))
    )",
    "CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT DEFAULT '',
        image TEXT DEFAULT '',
        is_active INTEGER DEFAULT 1,
        sort_order INTEGER DEFAULT 0,
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now')),
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS product_variations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        size_label TEXT NOT NULL,
        size_type TEXT NOT NULL,
        price REAL NOT NULL,
        is_active INTEGER DEFAULT 1,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS colors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        hex_code TEXT NOT NULL DEFAULT '#000000'
    )",
    "CREATE TABLE IF NOT EXISTS product_colors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        color_id INTEGER NOT NULL,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (color_id) REFERENCES colors(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_ref TEXT NOT NULL UNIQUE,
        customer_name TEXT NOT NULL,
        customer_email TEXT NOT NULL,
        customer_phone TEXT DEFAULT '',
        customer_address TEXT NOT NULL,
        customer_city TEXT NOT NULL,
        customer_zipcode TEXT NOT NULL,
        customer_country TEXT DEFAULT 'FR',
        items_json TEXT NOT NULL,
        subtotal REAL NOT NULL,
        shipping_cost REAL DEFAULT 0,
        shipping_method TEXT DEFAULT '',
        shipping_tracking TEXT DEFAULT '',
        total REAL NOT NULL,
        payment_id TEXT DEFAULT '',
        payment_status TEXT DEFAULT 'pending',
        order_status TEXT DEFAULT 'new',
        created_at TEXT DEFAULT (datetime('now')),
        updated_at TEXT DEFAULT (datetime('now'))
    )",
    "CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT DEFAULT '',
        name TEXT DEFAULT '',
        created_at TEXT DEFAULT (datetime('now'))
    )",
    "CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT
    )",
];

foreach ($tables as $sql) {
    $db->exec($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    $password = $_POST['admin_password'];
    if (strlen($password) < 6) {
        $message = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('admin_password_hash', ?)");
        $stmt->execute([$hash]);

        $stmt = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES ('site_name', ?)");
        $stmt->execute([SITE_NAME]);

        $installed = true;
        $message = 'Installation réussie ! Supprimez ce fichier install.php et connectez-vous à l\'admin.';
    }
}

$check = $db->query("SELECT value FROM settings WHERE key='admin_password_hash'")->fetch();
if ($check && !empty($check['value'])) {
    $installed = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-md w-full mx-4">
        <h1 class="text-2xl font-bold text-center mb-6">Installation</h1>

        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-lg <?= $installed ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($installed): ?>
            <p class="text-center text-gray-600">La base de données est configurée.</p>
            <a href="admin/login.php" class="block mt-4 text-center bg-teal-600 text-white py-2 rounded-xl hover:bg-teal-700 transition">
                Accéder à l'admin
            </a>
        <?php else: ?>
            <form method="POST">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe administrateur</label>
                <input type="password" name="admin_password" required minlength="6"
                       class="w-full border border-gray-300 rounded-xl px-4 py-2 mb-4 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <button type="submit"
                        class="w-full bg-teal-600 text-white py-2 rounded-xl hover:bg-teal-700 transition font-medium">
                    Installer
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
