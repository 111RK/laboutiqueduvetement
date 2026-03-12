<?php
require_once __DIR__ . '/../includes/admin-header.php';

$success = flash('success');

// Handle color actions
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

    if ($action === 'update_password') {
        $pw = $_POST['new_password'] ?? '';
        if (strlen($pw) >= 6) {
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('admin_password_hash', ?)");
            $stmt->execute([$hash]);
            flash('success', 'Mot de passe modifié.');
        } else {
            flash('success', 'Mot de passe trop court (min 6 caractères).');
        }
    }

    if ($action === 'update_api') {
        // Save API keys in settings table
        $keys = ['payplug_secret', 'payplug_public', 'packlink_api'];
        foreach ($keys as $k) {
            $val = trim($_POST[$k] ?? '');
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$k, $val]);
        }
        flash('success', 'Clés API mises à jour.');
    }

    header('Location: settings.php');
    exit;
}

$colors = $db->query("SELECT * FROM colors ORDER BY name")->fetchAll();

// Load API settings
$api_settings = [];
$rows = $db->query("SELECT key, value FROM settings WHERE key IN ('payplug_secret','payplug_public','packlink_api')")->fetchAll();
foreach ($rows as $r) $api_settings[$r['key']] = $r['value'];
?>

<h1 class="text-2xl font-bold mb-6">Paramètres</h1>

<?php if ($success): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded-xl mb-4 text-sm"><?= h($success) ?></div>
<?php endif; ?>

<div class="space-y-6 max-w-2xl">

    <!-- Colors Management -->
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

    <!-- API Keys -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Clés API</h2>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_api">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PayPlug - Clé secrète</label>
                <input type="text" name="payplug_secret" value="<?= h($api_settings['payplug_secret'] ?? '') ?>"
                       placeholder="sk_live_..." class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PayPlug - Clé publique</label>
                <input type="text" name="payplug_public" value="<?= h($api_settings['payplug_public'] ?? '') ?>"
                       placeholder="pk_live_..." class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Packlink - Clé API</label>
                <input type="text" name="packlink_api" value="<?= h($api_settings['packlink_api'] ?? '') ?>"
                       placeholder="Votre clé API Packlink" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
            </div>

            <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition">
                Enregistrer les clés API
            </button>
        </form>
    </div>

    <!-- Password -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Mot de passe admin</h2>
        <form method="POST" class="flex flex-col sm:flex-row gap-3">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_password">
            <input type="password" name="new_password" placeholder="Nouveau mot de passe (min 6 car.)" required minlength="6"
                   class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="bg-red-500 text-white px-6 py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition">
                Modifier
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
