<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_color') {
        $name = trim($_POST['color_name'] ?? '');
        $hex = trim($_POST['color_hex'] ?? '#000000');
        if ($name) {
            $stmt = $db->prepare("INSERT INTO colors (name, hex_code) VALUES (?, ?)");
            $stmt->execute([$name, $hex]);
            flash('success', 'Couleur ajoutée.');
        }
    }

    if ($action === 'delete_color') {
        $color_id = (int)($_POST['color_id'] ?? 0);
        $db->prepare("DELETE FROM product_colors WHERE color_id = ?")->execute([$color_id]);
        $db->prepare("DELETE FROM colors WHERE id = ?")->execute([$color_id]);
        flash('success', 'Couleur supprimée.');
    }

    if ($action === 'add_size') {
        $label = trim($_POST['size_label'] ?? '');
        $type = in_array($_POST['size_type'] ?? '', ['adult', 'child']) ? $_POST['size_type'] : 'adult';
        $price = (float)($_POST['size_price'] ?? 0);
        $sort = (int)($_POST['size_sort'] ?? 0);
        if ($label && $price > 0) {
            $stmt = $db->prepare("INSERT INTO sizes (label, type, default_price, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$label, $type, $price, $sort]);
            flash('success', 'Taille ajoutée.');
        }
    }

    if ($action === 'update_size') {
        $size_id = (int)($_POST['size_id'] ?? 0);
        $label = trim($_POST['size_label'] ?? '');
        $type = in_array($_POST['size_type'] ?? '', ['adult', 'child']) ? $_POST['size_type'] : 'adult';
        $price = (float)($_POST['size_price'] ?? 0);
        $sort = (int)($_POST['size_sort'] ?? 0);
        if ($size_id && $label && $price > 0) {
            $stmt = $db->prepare("UPDATE sizes SET label = ?, type = ?, default_price = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$label, $type, $price, $sort, $size_id]);
            flash('success', 'Taille modifiée.');
        }
    }

    if ($action === 'delete_size') {
        $size_id = (int)($_POST['size_id'] ?? 0);
        $db->prepare("DELETE FROM sizes WHERE id = ?")->execute([$size_id]);
        flash('success', 'Taille supprimée.');
    }

    header('Location: attributes.php');
    exit;
}

require_once __DIR__ . '/../includes/admin-header.php';

$success = flash('success');
$colors = $db->query("SELECT * FROM colors ORDER BY name")->fetchAll();
$sizes_adult = $db->query("SELECT * FROM sizes WHERE type = 'adult' ORDER BY sort_order, label")->fetchAll();
$sizes_child = $db->query("SELECT * FROM sizes WHERE type = 'child' ORDER BY sort_order, label")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Attributs</h1>

<?php if ($success): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded-xl mb-4 text-sm"><?= h($success) ?></div>
<?php endif; ?>

<div class="space-y-6 max-w-2xl">

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Tailles</h2>

        <form method="POST" class="flex flex-wrap gap-3 mb-4 items-end">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add_size">
            <div class="flex-1 min-w-[120px]">
                <label class="block text-xs text-gray-500 mb-1">Nom</label>
                <input type="text" name="size_label" placeholder="Ex: XS, 3/4 ans" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="w-32">
                <label class="block text-xs text-gray-500 mb-1">Type</label>
                <select name="size_type" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="adult">Adulte</option>
                    <option value="child">Enfant</option>
                </select>
            </div>
            <div class="w-24">
                <label class="block text-xs text-gray-500 mb-1">Prix €</label>
                <input type="number" name="size_price" step="0.01" min="0.01" value="6.00" required
                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <div class="w-20">
                <label class="block text-xs text-gray-500 mb-1">Ordre</label>
                <input type="number" name="size_sort" value="0"
                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            </div>
            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition">
                Ajouter
            </button>
        </form>

        <?php if ($sizes_adult): ?>
            <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2 mt-4">Adulte</h3>
            <div class="space-y-2 mb-4">
                <?php foreach ($sizes_adult as $s): ?>
                    <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-4 py-2.5">
                        <span class="text-sm font-medium flex-1"><?= h($s['label']) ?></span>
                        <span class="text-sm text-gray-500"><?= number_format($s['default_price'], 2, ',', '') ?> €</span>
                        <span class="text-xs text-gray-400">ordre: <?= $s['sort_order'] ?></span>
                        <button type="button" onclick="editSize(<?= $s['id'] ?>, '<?= h(addslashes($s['label'])) ?>', '<?= $s['type'] ?>', <?= $s['default_price'] ?>, <?= $s['sort_order'] ?>)"
                                class="text-primary-600 hover:text-primary-800 ml-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette taille ?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_size">
                            <input type="hidden" name="size_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($sizes_child): ?>
            <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2 mt-4">Enfant</h3>
            <div class="space-y-2">
                <?php foreach ($sizes_child as $s): ?>
                    <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-4 py-2.5">
                        <span class="text-sm font-medium flex-1"><?= h($s['label']) ?></span>
                        <span class="text-sm text-gray-500"><?= number_format($s['default_price'], 2, ',', '') ?> €</span>
                        <span class="text-xs text-gray-400">ordre: <?= $s['sort_order'] ?></span>
                        <button type="button" onclick="editSize(<?= $s['id'] ?>, '<?= h(addslashes($s['label'])) ?>', '<?= $s['type'] ?>', <?= $s['default_price'] ?>, <?= $s['sort_order'] ?>)"
                                class="text-primary-600 hover:text-primary-800 ml-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette taille ?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_size">
                            <input type="hidden" name="size_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$sizes_adult && !$sizes_child): ?>
            <p class="text-sm text-gray-400">Aucune taille définie.</p>
        <?php endif; ?>
    </div>

    <div id="size-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-black/30" onclick="closeSizeModal()"></div>
        <div class="bg-white rounded-2xl p-6 shadow-xl max-w-sm w-full mx-4 relative z-10">
            <h3 class="font-bold mb-4">Modifier la taille</h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="update_size">
                <input type="hidden" name="size_id" id="edit-size-id">
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Nom</label>
                    <input type="text" name="size_label" id="edit-size-label" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select name="size_type" id="edit-size-type" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="adult">Adulte</option>
                        <option value="child">Enfant</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-xs text-gray-500 mb-1">Prix par défaut (€)</label>
                    <input type="number" name="size_price" id="edit-size-price" step="0.01" min="0.01" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="mb-4">
                    <label class="block text-xs text-gray-500 mb-1">Ordre</label>
                    <input type="number" name="size_sort" id="edit-size-sort"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeSizeModal()" class="flex-1 py-2 border rounded-xl text-sm">Annuler</button>
                    <button type="submit" class="flex-1 py-2 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Couleurs</h2>

        <form method="POST" class="flex flex-wrap gap-3 mb-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add_color">
            <input type="text" name="color_name" placeholder="Nom (ex: Rouge)" required
                   class="flex-1 min-w-[150px] border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            <input type="color" name="color_hex" value="#FF0000"
                   class="w-12 h-10 border border-gray-200 rounded-xl cursor-pointer">
            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition">
                Ajouter
            </button>
        </form>

        <?php if ($colors): ?>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($colors as $c): ?>
                    <div class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2">
                        <div class="w-6 h-6 rounded-full border border-gray-200" style="background-color: <?= h($c['hex_code']) ?>"></div>
                        <span class="text-sm"><?= h($c['name']) ?></span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_color">
                            <input type="hidden" name="color_id" value="<?= $c['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-600 ml-1" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-400">Aucune couleur définie.</p>
        <?php endif; ?>
    </div>

</div>

<script>
function editSize(id, label, type, price, sort) {
    document.getElementById('edit-size-id').value = id;
    document.getElementById('edit-size-label').value = label;
    document.getElementById('edit-size-type').value = type;
    document.getElementById('edit-size-price').value = price;
    document.getElementById('edit-size-sort').value = sort;
    document.getElementById('size-modal').classList.remove('hidden');
}
function closeSizeModal() {
    document.getElementById('size-modal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
