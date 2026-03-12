<?php
require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$items = json_decode($order['items_json'], true) ?: [];

// Handle tracking update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tracking'])) {
    csrf_verify();
    $tracking = trim($_POST['tracking']);
    $stmt = $db->prepare("UPDATE orders SET shipping_tracking = ?, updated_at = datetime('now') WHERE id = ?");
    $stmt->execute([$tracking, $id]);
    $order['shipping_tracking'] = $tracking;
}
?>

<div class="flex items-center gap-3 mb-6">
    <a href="orders.php" class="p-2 hover:bg-gray-200 rounded-xl transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-bold">Commande <?= h($order['order_ref']) ?></h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 max-w-4xl">
    <!-- Customer -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-3">Client</h2>
        <p class="font-medium"><?= h($order['customer_name']) ?></p>
        <p class="text-sm text-gray-500"><?= h($order['customer_email']) ?></p>
        <?php if ($order['customer_phone']): ?>
            <p class="text-sm text-gray-500"><?= h($order['customer_phone']) ?></p>
        <?php endif; ?>
        <p class="text-sm text-gray-500 mt-2">
            <?= h($order['customer_address']) ?><br>
            <?= h($order['customer_zipcode']) ?> <?= h($order['customer_city']) ?>
        </p>
    </div>

    <!-- Status -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-3">Statut</h2>
        <div class="space-y-2 text-sm">
            <p>Paiement :
                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                    <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                    <?= $order['payment_status'] === 'paid' ? 'Payé' : 'En attente' ?>
                </span>
            </p>
            <p>Commande :
                <form method="POST" action="order-update.php" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <select name="order_status" onchange="this.form.submit()"
                            class="text-xs border border-gray-200 rounded-lg px-2 py-1">
                        <option value="new" <?= $order['order_status'] === 'new' ? 'selected' : '' ?>>Nouvelle</option>
                        <option value="confirmed" <?= $order['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmée</option>
                        <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Expédiée</option>
                        <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Livrée</option>
                    </select>
                </form>
            </p>
            <p>Livraison : <?= h($order['shipping_method'] ?: 'Non défini') ?></p>
        </div>

        <!-- Tracking -->
        <form method="POST" class="mt-4 flex gap-2">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="text" name="tracking" value="<?= h($order['shipping_tracking']) ?>"
                   placeholder="N° de suivi"
                   class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="bg-primary-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-primary-700 transition">Sauver</button>
        </form>
    </div>

    <!-- Items -->
    <div class="bg-white rounded-xl shadow-sm p-5 lg:col-span-2">
        <h2 class="font-bold mb-3">Articles commandés</h2>
        <div class="space-y-3">
            <?php foreach ($items as $item): ?>
                <div class="flex items-center gap-3 pb-3 border-b last:border-0">
                    <?php if (!empty($item['image'])): ?>
                        <img src="../<?= h(UPLOAD_URL . $item['image']) ?>" class="w-12 h-12 rounded-lg object-cover">
                    <?php else: ?>
                        <div class="w-12 h-12 bg-gray-100 rounded-lg"></div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-medium text-sm"><?= h($item['title']) ?></p>
                        <p class="text-xs text-gray-400">
                            Taille : <?= h($item['size_label']) ?>
                            <?= !empty($item['color_name']) ? ' | Couleur : ' . h($item['color_name']) : '' ?>
                            | Qté : <?= $item['qty'] ?>
                        </p>
                    </div>
                    <span class="font-bold text-sm"><?= format_price($item['price'] * $item['qty']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 pt-3 border-t text-sm space-y-1">
            <div class="flex justify-between"><span>Sous-total</span><span><?= format_price($order['subtotal']) ?></span></div>
            <div class="flex justify-between"><span>Livraison</span><span><?= format_price($order['shipping_cost']) ?></span></div>
            <div class="flex justify-between font-bold text-lg mt-2 pt-2 border-t"><span>Total</span><span class="text-primary-700"><?= format_price($order['total']) ?></span></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
