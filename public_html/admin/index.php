<?php
require_once __DIR__ . '/../includes/admin-header.php';

$product_count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$active_count = $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$category_count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$order_count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$recent_orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Tableau de bord</h1>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-sm text-gray-400">Produits</p>
        <p class="text-2xl font-bold mt-1"><?= $product_count ?></p>
        <p class="text-xs text-green-500 mt-1"><?= $active_count ?> actifs</p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-sm text-gray-400">Catégories</p>
        <p class="text-2xl font-bold mt-1"><?= $category_count ?></p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-sm text-gray-400">Commandes</p>
        <p class="text-2xl font-bold mt-1"><?= $order_count ?></p>
    </div>
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <p class="text-sm text-gray-400">Chiffre d'affaires</p>
        <p class="text-2xl font-bold mt-1"><?= format_price($revenue) ?></p>
    </div>
</div>

<?php if ($recent_orders): ?>
<div class="bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-bold mb-4">Dernières commandes</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-400 border-b">
                <th class="pb-2">Réf.</th><th class="pb-2">Client</th><th class="pb-2">Total</th><th class="pb-2">Paiement</th><th class="pb-2">Date</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent_orders as $o): ?>
                <tr class="border-b last:border-0">
                    <td class="py-2 font-medium"><?= h($o['order_ref']) ?></td>
                    <td class="py-2"><?= h($o['customer_name']) ?></td>
                    <td class="py-2 font-bold"><?= format_price($o['total']) ?></td>
                    <td class="py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            <?= $o['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : ($o['payment_status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                            <?= $o['payment_status'] === 'paid' ? 'Payé' : ($o['payment_status'] === 'failed' ? 'Échoué' : 'En attente') ?>
                        </span>
                    </td>
                    <td class="py-2 text-gray-400"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
