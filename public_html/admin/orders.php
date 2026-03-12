<?php
require_once __DIR__ . '/../includes/admin-header.php';

$filter = $_GET['status'] ?? '';
$where = '';
$params = [];

if ($filter) {
    $where = 'WHERE order_status = ?';
    $params[] = $filter;
}

$stmt = $db->prepare("SELECT * FROM orders $where ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$counts = $db->query("SELECT order_status, COUNT(*) as c FROM orders GROUP BY order_status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<h1 class="text-2xl font-bold mb-6">Commandes</h1>

<!-- Status filters -->
<div class="flex flex-wrap gap-2 mb-6">
    <a href="orders.php" class="px-4 py-1.5 rounded-full text-sm font-medium <?= !$filter ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
        Toutes (<?= array_sum($counts) ?>)
    </a>
    <?php foreach (['new' => 'Nouvelles', 'confirmed' => 'Confirmées', 'shipped' => 'Expédiées', 'delivered' => 'Livrées'] as $s => $label): ?>
        <a href="orders.php?status=<?= $s ?>" class="px-4 py-1.5 rounded-full text-sm font-medium <?= $filter === $s ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition">
            <?= $label ?> (<?= $counts[$s] ?? 0 ?>)
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($orders)): ?>
        <p class="p-8 text-center text-gray-400">Aucune commande</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-400 border-b bg-gray-50">
                    <th class="p-4">Réf.</th><th class="p-4">Client</th><th class="p-4">Articles</th><th class="p-4">Total</th><th class="p-4">Paiement</th><th class="p-4">Statut</th><th class="p-4">Date</th><th class="p-4">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($orders as $o):
                    $items = json_decode($o['items_json'], true);
                    $item_count = $items ? array_sum(array_column($items, 'qty')) : 0;
                ?>
                    <tr class="border-b last:border-0 hover:bg-gray-50">
                        <td class="p-4 font-mono font-medium text-xs"><?= h($o['order_ref']) ?></td>
                        <td class="p-4">
                            <p class="font-medium"><?= h($o['customer_name']) ?></p>
                            <p class="text-xs text-gray-400"><?= h($o['customer_email']) ?></p>
                        </td>
                        <td class="p-4"><?= $item_count ?> article<?= $item_count > 1 ? 's' : '' ?></td>
                        <td class="p-4 font-bold"><?= format_price($o['total']) ?></td>
                        <td class="p-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                <?= $o['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : ($o['payment_status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                <?= $o['payment_status'] === 'paid' ? 'Payé' : ($o['payment_status'] === 'failed' ? 'Échoué' : 'En attente') ?>
                            </span>
                        </td>
                        <td class="p-4">
                            <form method="POST" action="order-update.php" class="inline">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <select name="order_status" onchange="this.form.submit()"
                                        class="text-xs border border-gray-200 rounded-lg px-2 py-1 focus:ring-1 focus:ring-primary-500">
                                    <option value="new" <?= $o['order_status'] === 'new' ? 'selected' : '' ?>>Nouvelle</option>
                                    <option value="confirmed" <?= $o['order_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmée</option>
                                    <option value="shipped" <?= $o['order_status'] === 'shipped' ? 'selected' : '' ?>>Expédiée</option>
                                    <option value="delivered" <?= $o['order_status'] === 'delivered' ? 'selected' : '' ?>>Livrée</option>
                                </select>
                            </form>
                        </td>
                        <td class="p-4 text-gray-400 text-xs"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        <td class="p-4">
                            <a href="order-detail.php?id=<?= $o['id'] ?>" class="text-primary-600 hover:underline text-sm">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
