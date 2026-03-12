<?php
require_once __DIR__ . '/../includes/admin-header.php';

$success = flash('success');

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

    if ($action === 'add_admin') {
        $login = trim($_POST['admin_login'] ?? '');
        $pw = $_POST['admin_password'] ?? '';
        if ($login && strlen($pw) >= 4) {
            $hash = password_hash($pw, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT OR REPLACE INTO admins (login, password_hash) VALUES (?, ?)");
            $stmt->execute([$login, $hash]);
            flash('success', "Compte admin '$login' créé/mis à jour.");
        } else {
            flash('success', 'Login requis et mot de passe min 4 caractères.');
        }
    }

    if ($action === 'delete_admin') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        $count = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ($count > 1) {
            $db->prepare("DELETE FROM admins WHERE id = ?")->execute([$admin_id]);
            flash('success', 'Compte admin supprimé.');
        } else {
            flash('success', 'Impossible de supprimer le dernier admin.');
        }
    }

    if ($action === 'update_api') {
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
$admins = $db->query("SELECT * FROM admins ORDER BY login")->fetchAll();

$api_settings = [];
$rows = $db->query("SELECT key, value FROM settings WHERE key IN ('payplug_secret','payplug_public','packlink_api')")->fetchAll();
foreach ($rows as $r) $api_settings[$r['key']] = $r['value'];
?>

<h1 class="text-2xl font-bold mb-6">Paramètres</h1>

<?php if ($success): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded-xl mb-4 text-sm"><?= h($success) ?></div>
<?php endif; ?>

<div class="space-y-6 max-w-2xl">

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

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h2 class="font-bold mb-4">Comptes administrateurs</h2>

        <form method="POST" class="flex flex-wrap gap-3 mb-4">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="add_admin">
            <input type="text" name="admin_login" placeholder="Login" required
                   class="flex-1 min-w-[120px] border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            <input type="password" name="admin_password" placeholder="Mot de passe" required minlength="4"
                   class="flex-1 min-w-[120px] border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary-500">
            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-primary-700 transition">
                Ajouter / Modifier
            </button>
        </form>

        <?php if ($admins): ?>
            <div class="space-y-2">
                <?php foreach ($admins as $a): ?>
                    <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2.5">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span class="text-sm font-medium"><?= h($a['login']) ?></span>
                        </div>
                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce compte ?')">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_admin">
                            <input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-600 text-sm">Supprimer</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-gray-400">Aucun compte admin.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
