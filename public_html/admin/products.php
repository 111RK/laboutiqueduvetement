<?php
require_once __DIR__ . '/../includes/admin-header.php';

$success = flash('success');

$products = $db->query("
    SELECT p.*, c.name AS category_name,
           GROUP_CONCAT(DISTINCT pv.size_label) as sizes,
           MIN(pv.price) AS min_price, MAX(pv.price) AS max_price
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variations pv ON pv.product_id = p.id
    GROUP BY p.id
    ORDER BY p.sort_order, p.created_at DESC
")->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Produits</h1>
    <a href="product-form.php" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Ajouter un produit
    </a>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded-xl mb-4 text-sm"><?= h($success) ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($products)): ?>
        <div class="p-12 text-center">
            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <p class="text-gray-400 mb-4">Aucun produit</p>
            <a href="product-form.php" class="text-primary-600 font-medium hover:underline">Ajouter votre premier produit</a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-400 border-b bg-gray-50">
                    <th class="p-4">Image</th><th class="p-4">Titre</th><th class="p-4">Catégorie</th><th class="p-4">Tailles</th><th class="p-4">Prix</th><th class="p-4">Statut</th><th class="p-4">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($products as $prod): ?>
                    <tr class="border-b last:border-0 hover:bg-gray-50">
                        <td class="p-4">
                            <?php if ($prod['image']): ?>
                                <img src="../<?= h(UPLOAD_URL . $prod['image']) ?>" class="w-12 h-12 rounded-lg object-cover">
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded-lg"></div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 font-medium"><?= h($prod['title']) ?></td>
                        <td class="p-4 text-gray-500"><?= h($prod['category_name'] ?? '—') ?></td>
                        <td class="p-4 text-xs text-gray-400"><?= h($prod['sizes'] ?? '—') ?></td>
                        <td class="p-4">
                            <?php if ($prod['min_price'] !== null): ?>
                                <?= format_price($prod['min_price']) ?><?= $prod['min_price'] != $prod['max_price'] ? ' - ' . format_price($prod['max_price']) : '' ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="p-4">
                            <button onclick="toggleActive(<?= $prod['id'] ?>, this)"
                                    class="px-2 py-0.5 rounded-full text-xs font-medium <?= $prod['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                <?= $prod['is_active'] ? 'Actif' : 'Masqué' ?>
                            </button>
                        </td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <a href="product-form.php?id=<?= $prod['id'] ?>" class="text-primary-600 hover:underline">Modifier</a>
                                <form method="POST" action="product-delete.php" onsubmit="return confirm('Supprimer ce produit ?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                    <button type="submit" class="text-red-500 hover:underline">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleActive(id, btn) {
    fetch('ajax/toggle-active.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id + '&csrf_token=<?= csrf_token() ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (data.is_active) {
            btn.textContent = 'Actif';
            btn.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700';
        } else {
            btn.textContent = 'Masqué';
            btn.className = 'px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
