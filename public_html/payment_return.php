<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$order_ref = $_GET['order'] ?? '';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_ref = ?");
$stmt->execute([$order_ref]);
$order = $stmt->fetch();

// Check payment status with PayPlug
if ($order && $order['payment_id'] && PAYPLUG_SECRET_KEY) {
    $ch = curl_init('https://api.payplug.com/v1/payments/' . $order['payment_id']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . PAYPLUG_SECRET_KEY,
            'PayPlug-Version: 2019-08-06',
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $payment = json_decode($response, true);
        if ($payment['is_paid'] ?? false) {
            $stmt = $db->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'confirmed', updated_at = datetime('now') WHERE id = ?");
            $stmt->execute([$order['id']]);
            $order['payment_status'] = 'paid';
        }
    }
}

$success = $order && $order['payment_status'] === 'paid';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center mx-4">
        <?php if ($success): ?>
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold mb-2">Merci pour votre commande !</h1>
            <p class="text-gray-500 mb-2">Votre paiement a été accepté.</p>
            <p class="text-sm text-gray-400 mb-6">Référence : <strong><?= h($order_ref) ?></strong></p>
            <p class="text-sm text-gray-500 mb-6">Un email de confirmation vous sera envoyé à <strong><?= h($order['customer_email']) ?></strong></p>
        <?php else: ?>
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold mb-2">Paiement en attente</h1>
            <p class="text-gray-500 mb-6">Le paiement n'a pas encore été confirmé. Veuillez réessayer ou nous contacter.</p>
        <?php endif; ?>
        <a href="index.php" class="inline-block bg-teal-600 text-white px-6 py-2.5 rounded-xl font-medium hover:bg-teal-700 transition">
            Retour à la boutique
        </a>
    </div>
</body>
</html>
