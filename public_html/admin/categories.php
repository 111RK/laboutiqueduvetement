<?php
require_once __DIR__ . '/../includes/admin-header.php';

$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);

        if ($name) {
            $slug = slugify($name);
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $sort_order]);
                flash('success', 'Catégorie ajoutée.');
            } else {
                $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $sort_order, $id]);
                flash('success', 'Catégorie modifiée.');
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        flash('success', 'Catégorie supprimée.');
    }

    header('Location: categories.php');
    exit;
}

$categories = $db->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.sort_order, c.name")->fetchAll();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Catégories</h1>
</div>

<?php if ($success): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded-xl mb-4 text-sm"><?= h($success) ?></div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h2 class="font-bold mb-3">Ajouter une catégorie</h2>
    <form method="POST" class="flex flex-col sm:flex-row gap-3">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" placeholder="Nom de la catégorie" required
               class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        <input type="number" name="sort_order" placeholder="Ordre" value="0"
               class="w-24 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition">
            Ajouter
        </button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($categories)): ?>
        <p class="p-8 text-center text-gray-400">Aucune catégorie. Ajoutez-en une ci-dessus.</p>
    <?php else: ?>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-400 border-b bg-gray-50">
                <th class="p-4">Nom</th><th class="p-4">Ordre</th><th class="p-4">Produits</th><th class="p-4">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr class="border-b last:border-0 hover:bg-gray-50" id="cat-<?= $cat['id'] ?>">
                    <td class="p-4 font-medium"><?= h($cat['name']) ?></td>
                    <td class="p-4"><?= $cat['sort_order'] ?></td>
                    <td class="p-4"><?= $cat['product_count'] ?></td>
                    <td class="p-4 flex gap-2">
                        <button onclick="editCategory(<?= $cat['id'] ?>, '<?= h(addslashes($cat['name'])) ?>', <?= $cat['sort_order'] ?>)"
                                class="text-primary-600 hover:underline text-sm">Modifier</button>
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" class="text-red-500 hover:underline text-sm">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="edit-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/30" onclick="closeEditModal()"></div>
    <div class="bg-white rounded-2xl p-6 shadow-xl max-w-sm w-full mx-4 relative z-10">
        <h3 class="font-bold mb-4">Modifier la catégorie</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <input type="text" name="name" id="edit-name" required
                   class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm mb-3 focus:ring-2 focus:ring-primary-500">
            <input type="number" name="sort_order" id="edit-order"
                   class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm mb-4 focus:ring-2 focus:ring-primary-500">
            <div class="flex gap-2">
                <button type="button" onclick="closeEditModal()" class="flex-1 py-2 border rounded-xl text-sm">Annuler</button>
                <button type="submit" class="flex-1 py-2 bg-primary-600 text-white rounded-xl text-sm font-medium hover:bg-primary-700">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, order) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-order').value = order;
    document.getElementById('edit-modal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
