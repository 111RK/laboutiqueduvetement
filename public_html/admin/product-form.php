<?php
require_once __DIR__ . '/../includes/admin-header.php';

$id = (int)($_GET['id'] ?? 0);
$product = null;
$variations = [];
$product_colors = [];

$categories = $db->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$sizes = ['adult' => [], 'child' => []];
$db_sizes = $db->query("SELECT * FROM sizes ORDER BY sort_order, label")->fetchAll();
foreach ($db_sizes as $sz) {
    $sizes[$sz['type']][] = ['label' => $sz['label'], 'price' => $sz['default_price']];
}

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
            <div class="relative">
                <textarea name="description" id="description-field" rows="3"
                          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                          placeholder="Description du produit..."><?= h($product['description'] ?? '') ?></textarea>
                <button type="button" onclick="generateDescription()"
                        class="absolute top-2 right-2 bg-primary-100 text-primary-700 px-3 py-1 rounded-lg text-xs font-medium hover:bg-primary-200 transition">
                    Auto-générer
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Cliquez "Auto-générer" pour créer une description SEO basée sur le titre</p>
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

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Tailles et tarifs</h2>

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

    <div class="flex gap-3">
        <button type="submit" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-medium hover:bg-primary-700 transition">
            <?= $id ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
        </button>
        <a href="products.php" class="px-6 py-3 border border-gray-200 rounded-xl text-sm hover:bg-gray-50 transition">Annuler</a>
    </div>
</form>

<script>
function generateDescription() {
    const title = document.querySelector('[name="title"]').value.trim();
    const catSelect = document.querySelector('[name="category_id"]');
    const catName = catSelect.options[catSelect.selectedIndex]?.text?.trim() || '';
    const desc = document.getElementById('description-field');

    if (!title) {
        alert('Renseignez d\'abord le titre du produit.');
        return;
    }

    const checkedSizes = [];
    document.querySelectorAll('input[type="checkbox"][name*="[active]"]:checked').forEach(cb => {
        const label = cb.closest('.flex')?.querySelector('span')?.textContent?.trim();
        if (label) checkedSizes.push(label);
    });

    const checkedColors = [];
    document.querySelectorAll('input[type="checkbox"][name="colors[]"]:checked').forEach(cb => {
        const label = cb.closest('label')?.querySelector('span')?.textContent?.trim();
        if (label) checkedColors.push(label);
    });

    const titleLower = title.toLowerCase();
    let visual = '';

    const keywords = {
        'fleur': 'un motif floral élégant',
        'rose': 'un design aux tons rosés',
        'coeur': 'un motif cœur tendance',
        'étoile': 'un design étoilé scintillant',
        'star': 'un design étoilé',
        'animal': 'un imprimé animalier',
        'chat': 'un adorable motif chat',
        'chien': 'un motif chien attachant',
        'lion': 'un imprimé lion majestueux',
        'papillon': 'un motif papillon délicat',
        'dragon': 'un design dragon audacieux',
        'skull': 'un motif tête de mort stylisé',
        'tête de mort': 'un motif tête de mort stylisé',
        'flamme': 'un design flammes dynamique',
        'tribal': 'un motif tribal graphique',
        'mandala': 'un motif mandala apaisant',
        'geometr': 'un design géométrique moderne',
        'abstrait': 'un motif abstrait artistique',
        'vintage': 'un style rétro vintage',
        'retro': 'un style rétro tendance',
        'surf': 'un design esprit surf et plage',
        'montagne': 'un motif montagne nature',
        'nature': 'un design inspiré de la nature',
        'lune': 'un motif lune mystique',
        'soleil': 'un design soleil lumineux',
        'space': 'un design spatial cosmique',
        'galaxie': 'un motif galaxie envoûtant',
        'musique': 'un design musical',
        'rock': 'un design rock\'n\'roll',
        'sport': 'un motif sportif dynamique',
        'foot': 'un design football',
        'basket': 'un design basketball',
        'drapeau': 'un motif drapeau patriotique',
        'france': 'un design aux couleurs de la France',
        'noël': 'un motif festif de Noël',
        'halloween': 'un design Halloween',
        'humour': 'un visuel humoristique',
        'drôle': 'un design fun et décalé',
        'citation': 'une citation inspirante',
        'texte': 'un message typographique',
        'logo': 'un logo moderne',
        'paillette': 'un effet pailleté brillant',
        'tie-dye': 'un effet tie-dye coloré',
        'camouflage': 'un imprimé camouflage',
        'rayure': 'un motif rayé classique'
    };

    for (const [key, desc_text] of Object.entries(keywords)) {
        if (titleLower.includes(key)) {
            visual = desc_text;
            break;
        }
    }

    if (!visual) {
        visual = 'un visuel original et tendance';
    }

    let type = 'vêtement';
    if (titleLower.includes('t-shirt') || titleLower.includes('tshirt') || titleLower.includes('tee')) type = 't-shirt';
    else if (titleLower.includes('sweat')) type = 'sweat';
    else if (titleLower.includes('hoodie') || titleLower.includes('capuche')) type = 'hoodie';
    else if (titleLower.includes('polo')) type = 'polo';
    else if (titleLower.includes('débardeur') || titleLower.includes('tank')) type = 'débardeur';
    else if (titleLower.includes('veste')) type = 'veste';
    else if (titleLower.includes('pantalon')) type = 'pantalon';
    else if (titleLower.includes('short')) type = 'short';
    else if (titleLower.includes('casquette') || titleLower.includes('bonnet')) type = 'accessoire';
    else if (titleLower.includes('body') || titleLower.includes('bébé')) type = 'body bébé';

    let description = `${title} — ${type.charAt(0).toUpperCase() + type.slice(1)} orné de ${visual}.`;

    if (catName && catName !== '— Sans catégorie —') {
        description += ` Idéal dans notre collection ${catName}.`;
    }

    if (checkedColors.length) {
        description += ` Disponible en ${checkedColors.join(', ')}.`;
    }

    if (checkedSizes.length) {
        const hasAdult = checkedSizes.some(s => ['XS','S','M','L','XL','XXL'].includes(s));
        const hasChild = checkedSizes.some(s => s.includes('ans'));
        if (hasAdult && hasChild) {
            description += ' Tailles adulte et enfant disponibles.';
        } else if (hasChild) {
            description += ' Tailles enfant disponibles.';
        } else {
            description += ' Tailles adulte disponibles.';
        }
    }

    description += ' Qualité premium, impression durable. Livraison rapide en France.';

    desc.value = description;
}
</script>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
