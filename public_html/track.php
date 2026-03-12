<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$order = null;
$orders_list = [];
$error = '';
$is_customer = false;

// Check if customer is logged in
$customer_id = $_SESSION['customer_id'] ?? null;

if ($customer_id) {
    $is_customer = true;
    $stmt = $db->prepare("SELECT email FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if ($customer) {
        $stmt = $db->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC");
        $stmt->execute([$customer['email']]);
        $orders_list = $stmt->fetchAll();
    }
}

// Handle order lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'lookup';

    if ($action === 'lookup') {
        $email = trim($_POST['email'] ?? '');
        $ref = trim($_POST['order_ref'] ?? '');

        if ($email && $ref) {
            $stmt = $db->prepare("SELECT * FROM orders WHERE customer_email = ? AND order_ref = ?");
            $stmt->execute([$email, $ref]);
            $order = $stmt->fetch();

            if (!$order) {
                $error = 'Aucune commande trouvée avec ces informations.';
            }
        } else {
            $error = 'Veuillez renseigner votre email et numéro de commande.';
        }
    }

    if ($action === 'create_password') {
        csrf_verify();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && strlen($password) >= 6) {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Check if customer exists
            $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("UPDATE customers SET password_hash = ? WHERE id = ?");
                $stmt->execute([$hash, $existing['id']]);
                $customer_id = $existing['id'];
            } else {
                $stmt = $db->prepare("INSERT INTO customers (email, password_hash) VALUES (?, ?)");
                $stmt->execute([$email, $hash]);
                $customer_id = $db->lastInsertId();
            }

            $_SESSION['customer_id'] = $customer_id;
            header('Location: track.php');
            exit;
        } else {
            $error = 'Mot de passe trop court (minimum 6 caractères).';
        }
    }

    if ($action === 'customer_login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $db->prepare("SELECT * FROM customers WHERE email = ? AND password_hash != ''");
        $stmt->execute([$email]);
        $cust = $stmt->fetch();

        if ($cust && password_verify($password, $cust['password_hash'])) {
            $_SESSION['customer_id'] = $cust['id'];
            header('Location: track.php');
            exit;
        } else {
            $error = 'Identifiants incorrects.';
        }
    }
}

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['customer_id']);
    header('Location: track.php');
    exit;
}

$status_labels = [
    'new' => ['Commande reçue', 'bg-blue-100 text-blue-700'],
    'confirmed' => ['Confirmée', 'bg-green-100 text-green-700'],
    'shipped' => ['Expédiée', 'bg-purple-100 text-purple-700'],
    'delivered' => ['Livrée', 'bg-teal-100 text-teal-700'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de commande - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            primary: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' }
        }}}}
    </script>
</head>
<body class="bg-gray-50 min-h-screen">

<header class="bg-white shadow-sm">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="index.php" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-lg font-bold">Suivi de commande</h1>
        </div>
        <?php if ($is_customer): ?>
            <a href="track.php?logout=1" class="text-sm text-red-500 hover:underline">Déconnexion</a>
        <?php endif; ?>
    </div>
</header>

<div class="max-w-3xl mx-auto px-4 py-8">

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-600 text-sm p-3 rounded-xl mb-6"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($is_customer && $orders_list): ?>
        <!-- Customer logged in - show all orders -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-5 border-b">
                <h2 class="font-bold">Vos commandes</h2>
            </div>
            <?php foreach ($orders_list as $o):
                $sl = $status_labels[$o['order_status']] ?? ['Inconnu', 'bg-gray-100 text-gray-500'];
            ?>
                <div class="p-5 border-b last:border-0 hover:bg-gray-50">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <span class="font-mono font-bold text-sm"><?= h($o['order_ref']) ?></span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $sl[1] ?>"><?= $sl[0] ?></span>
                    </div>
                    <div class="flex flex-wrap justify-between text-sm text-gray-500">
                        <span><?= date('d/m/Y', strtotime($o['created_at'])) ?></span>
                        <span class="font-bold text-primary-700"><?= format_price($o['total']) ?></span>
                    </div>
                    <?php if ($o['shipping_tracking']): ?>
                        <p class="text-xs text-gray-400 mt-2">Suivi : <?= h($o['shipping_tracking']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($order): ?>
        <!-- Single order view -->
        <?php $sl = $status_labels[$order['order_status']] ?? ['Inconnu', 'bg-gray-100 text-gray-500']; ?>
        <div class="bg-white rounded-xl shadow-sm p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-lg"><?= h($order['order_ref']) ?></h2>
                <span class="px-3 py-1 rounded-full text-xs font-medium <?= $sl[1] ?>"><?= $sl[0] ?></span>
            </div>

            <!-- Progress bar -->
            <?php
            $steps = ['new', 'confirmed', 'shipped', 'delivered'];
            $current_step = array_search($order['order_status'], $steps);
            ?>
            <div class="flex items-center justify-between mb-6">
                <?php foreach ($steps as $i => $step):
                    $step_labels = ['Reçue', 'Confirmée', 'Expédiée', 'Livrée'];
                    $active = $i <= $current_step;
                ?>
                    <div class="flex-1 text-center">
                        <div class="w-8 h-8 rounded-full mx-auto mb-1 flex items-center justify-center text-sm font-bold
                            <?= $active ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-400' ?>">
                            <?= $active ? '&#10003;' : ($i + 1) ?>
                        </div>
                        <span class="text-xs <?= $active ? 'text-primary-700 font-medium' : 'text-gray-400' ?>"><?= $step_labels[$i] ?></span>
                    </div>
                    <?php if ($i < 3): ?>
                        <div class="flex-1 h-0.5 <?= $i < $current_step ? 'bg-primary-500' : 'bg-gray-200' ?> -mt-4"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="text-sm space-y-2 text-gray-600">
                <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></p>
                <p><strong>Total :</strong> <span class="text-primary-700 font-bold"><?= format_price($order['total']) ?></span></p>
                <?php if ($order['shipping_tracking']): ?>
                    <p><strong>N° de suivi :</strong> <?= h($order['shipping_tracking']) ?></p>
                <?php endif; ?>
                <p><strong>Paiement :</strong>
                    <?= $order['payment_status'] === 'paid' ? '<span class="text-green-600">Payé</span>' : '<span class="text-yellow-600">En attente</span>' ?>
                </p>
            </div>

            <!-- Items -->
            <?php $items = json_decode($order['items_json'], true) ?: []; ?>
            <?php if ($items): ?>
            <div class="mt-4 pt-4 border-t">
                <h3 class="font-medium mb-2">Articles</h3>
                <?php foreach ($items as $item): ?>
                    <div class="flex justify-between py-1 text-sm">
                        <span><?= h($item['title']) ?> (<?= h($item['size_label']) ?>) x<?= $item['qty'] ?></span>
                        <span class="font-medium"><?= format_price($item['price'] * $item['qty']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Create password option -->
        <?php
        $stmt = $db->prepare("SELECT id, password_hash FROM customers WHERE email = ?");
        $stmt->execute([trim($_POST['email'] ?? '')]);
        $cust = $stmt->fetch();
        $has_password = $cust && !empty($cust['password_hash']);
        ?>
        <?php if (!$has_password): ?>
            <div class="bg-primary-50 rounded-xl p-5 border border-primary-200">
                <h3 class="font-bold text-sm mb-2">Envie de suivre toutes vos commandes facilement ?</h3>
                <p class="text-sm text-gray-600 mb-3">Créez un mot de passe pour accéder à l'ensemble de vos commandes en un clic.</p>
                <form method="POST" class="flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="create_password">
                    <input type="hidden" name="email" value="<?= h(trim($_POST['email'] ?? '')) ?>">
                    <input type="password" name="password" placeholder="Choisir un mot de passe" required minlength="6"
                           class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                    <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition">
                        Créer
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <a href="track.php" class="block mt-4 text-center text-primary-600 hover:underline text-sm">Rechercher une autre commande</a>

    <?php else: ?>
        <!-- Lookup forms -->
        <div class="space-y-6">
            <!-- Simple lookup -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-bold mb-4">Suivre une commande</h2>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="lookup">
                    <input type="email" name="email" placeholder="Votre adresse email" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                    <input type="text" name="order_ref" placeholder="Numéro de commande (ex: LBV-XXXXXXXX)" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                    <button type="submit" class="w-full bg-primary-600 text-white py-2.5 rounded-xl font-medium hover:bg-primary-700 transition">
                        Rechercher
                    </button>
                </form>
            </div>

            <!-- Customer login -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-bold mb-2">Vous avez un compte ?</h2>
                <p class="text-sm text-gray-400 mb-4">Connectez-vous pour voir toutes vos commandes.</p>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="customer_login">
                    <input type="email" name="email" placeholder="Email" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                    <input type="password" name="password" placeholder="Mot de passe" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500">
                    <button type="submit" class="w-full bg-gray-800 text-white py-2.5 rounded-xl font-medium hover:bg-gray-900 transition">
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
