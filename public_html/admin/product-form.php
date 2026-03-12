<?php
require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
$product = null;
$variations = [];
$product_colors = [];

$categories = $db->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$sizes = json_decode(SIZES, true);

// Fetch predefined colors
$colors_result = $db->query("SELECT * FROM colors ORDER BY name");
$all_colors = $colors_result ? $colors_result->fetchAll() : [];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        $stmt = $db->prepare("SELECT * FROM product_variations WHERE product_id = ? ORDER BY id");
        $stmt->execute([$id]);
        $variations = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT color_id FROM product_colors WHERE product_id = ?");
        $stmt->execute([$id]);
        $product_colors = array_column($stmt->fetchAll(), 'color_id');
    }
}

$title = $product ? 'Modifier le produit' : 'Ajouter un produit';
?>

<div class="flex items-center gap-3 mb-6">
    <a href="products.php" class="p-2 hover:bg-gray-200 rounded-xl transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-bold"><?= $title ?></h1>
</div>

<form method="POST" action="product-save.php" enctype="multipart/form-data" class="space-y-4 max-w-3xl">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- Basic info -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Informations</h2>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Titre du produit *</label>
            <input type="text" name="title" required value="<?= h($product['title'] ?? '') ?>"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                   placeholder="Ex: T-shirt col rond">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="3"
                      class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      placeholder="Description du produit..."><?= h($product['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
            <select name="category_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <option value="">— Sans catégorie —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= h($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
            <input type="number" name="sort_order" value="<?= $product['sort_order'] ?? 0 ?>"
                   class="w-24 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        </div>
    </div>

    <!-- Image -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Image du produit</h2>
        <?php if ($product && $product['image']): ?>
            <div class="mb-3">
                <img src="../<?= h(UPLOAD_URL . $product['image']) ?>" class="w-32 h-32 rounded-xl object-cover">
                <label class="flex items-center gap-2 mt-2 text-sm text-gray-500">
                    <input type="checkbox" name="remove_image" value="1"> Supprimer l'image actuelle
                </label>
            </div>
        <?php endif; ?>
        <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-primary-300 transition cursor-pointer"
             onclick="document.getElementById('image-input').click()">
            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm text-gray-400" id="image-label">Cliquez pour choisir une image (JPG, PNG, WebP - max 5 Mo)</p>
            <input type="file" name="image" id="image-input" accept="image/jpeg,image/png,image/webp" class="hidden"
                   onchange="document.getElementById('image-label').textContent = this.files[0]?.name || 'Aucun fichier choisi'">
        </div>
    </div>

    <!-- Colors -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Couleurs disponibles</h2>
        <?php if (empty($all_colors)): ?>
            <p class="text-sm text-gray-400 mb-2">Aucune couleur définie. <a href="settings.php" class="text-primary-600 hover:underline">Gérer les couleurs</a></p>
        <?php else: ?>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($all_colors as $color): ?>
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="colors[]" value="<?= $color['id'] ?>"
                               <?= in_array($color['id'], $product_colors) ? 'checked' : '' ?>
                               class="hidden peer">
                        <div class="w-8 h-8 rounded-full border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:ring-2 peer-checked:ring-primary-200 transition"
                             style="background-color: <?= h($color['hex_code']) ?>"
                             title="<?= h($color['name']) ?>"></div>
                        <span class="text-xs text-gray-500"><?= h($color['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sizes & Prices -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Tailles et tarifs</h2>

        <!-- Adult sizes -->
        <div class="mb-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Adulte (défaut : 6,00 €)</h3>
            <div class="space-y-2">
                <?php foreach ($sizes['adult'] as $s):
                    $existing = array_filter($variations, fn($v) => $v['size_label'] === $s['label'] && $v['size_type'] === 'adult');
                    $existing = $existing ? array_values($existing)[0] : null;
                ?>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 w-28">
                        <input type="checkbox" name="sizes[adult][<?= $s['label'] ?>][active]" value="1"
                               <?= $existing ? 'checked' : '' ?>
                               class="rounded text-primary-600 focus:ring-primary-500"
                               onchange="this.closest('.flex').querySelector('input[type=number]').disabled = !this.checked">
                        <span class="text-sm font-medium"><?= $s['label'] ?></span>
                    </label>
                    <input type="number" name="sizes[adult][<?= $s['label'] ?>][price]"
                           value="<?= $existing ? $existing['price'] : $s['price'] ?>"
                           step="0.01" min="0"
                           <?= $existing ? '' : 'disabled' ?>
                           class="w-24 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 disabled:opacity-40">
                    <span class="text-xs text-gray-400">€</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Child sizes -->
        <div>
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">Enfant (défaut : 5,00 €)</h3>
            <div class="space-y-2">
                <?php foreach ($sizes['child'] as $s):
                    $existing = array_filter($variations, fn($v) => $v['size_label'] === $s['label'] && $v['size_type'] === 'child');
                    $existing = $existing ? array_values($existing)[0] : null;
                ?>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 w-28">
                        <input type="checkbox" name="sizes[child][<?= $s['label'] ?>][active]" value="1"
                               <?= $existing ? 'checked' : '' ?>
                               class="rounded text-primary-600 focus:ring-primary-500"
                               onchange="this.closest('.flex').querySelector('input[type=number]').disabled = !this.checked">
                        <span class="text-sm font-medium"><?= $s['label'] ?></span>
                    </label>
                    <input type="number" name="sizes[child][<?= $s['label'] ?>][price]"
                           value="<?= $existing ? $existing['price'] : $s['price'] ?>"
                           step="0.01" min="0"
                           <?= $existing ? '' : 'disabled' ?>
                           class="w-24 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-primary-500 disabled:opacity-40">
                    <span class="text-xs text-gray-400">€</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-3">
        <button type="submit" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-medium hover:bg-primary-700 transition">
            <?= $id ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
        </button>
        <a href="products.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm hover:bg-gray-50 transition">Annuler</a>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
