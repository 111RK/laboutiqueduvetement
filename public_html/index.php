<?php
require_once __DIR__ . '/includes/header.php';

$products = $db->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug,
           MIN(pv.price) AS min_price, MAX(pv.price) AS max_price
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_variations pv ON pv.product_id = p.id AND pv.is_active = 1
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY p.sort_order, p.created_at DESC
")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4">
    <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
        <?php if (empty($products)): ?>
            <div class="col-span-full text-center py-16">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-400 text-lg">Aucun article disponible pour le moment</p>
                <p class="text-gray-300 text-sm mt-1">Revenez bientôt !</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $prod): ?>
            <div class="product-card bg-white rounded-xl overflow-hidden shadow-sm cursor-pointer"
                 data-category="<?= h($prod['category_slug'] ?? 'none') ?>"
                 onclick="openProduct(<?= $prod['id'] ?>)">
                <div class="aspect-square bg-gray-100 overflow-hidden">
                    <?php if ($prod['image']): ?>
                        <img src="<?= h(UPLOAD_URL . $prod['image']) ?>"
                             alt="<?= h($prod['title']) ?>"
                             class="w-full h-full object-cover"
                             loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <h3 class="font-medium text-sm truncate"><?= h($prod['title']) ?></h3>
                    <?php if ($prod['category_name']): ?>
                        <p class="text-xs text-gray-400 mt-0.5"><?= h($prod['category_name']) ?></p>
                    <?php endif; ?>
                    <div class="mt-1.5">
                        <?php if ($prod['min_price'] !== null): ?>
                            <?php if ($prod['min_price'] == $prod['max_price']): ?>
                                <span class="text-primary-700 font-bold text-sm"><?= format_price($prod['min_price']) ?></span>
                            <?php else: ?>
                                <span class="text-primary-700 font-bold text-sm"><?= format_price($prod['min_price']) ?> - <?= format_price($prod['max_price']) ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
