<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

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
        $keys = [
            'payplug_test_secret', 'payplug_test_public',
            'payplug_live_secret', 'payplug_live_public',
            'packlink_api'
        ];
        foreach ($keys as $k) {
            $val = trim($_POST[$k] ?? '');
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$k, $val]);
        }
        $mode = ($_POST['payplug_mode'] ?? 'test') === 'live' ? 'live' : 'test';
        $stmt = $db->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
        $stmt->execute(['payplug_mode', $mode]);
        flash('success', 'Clés API mises à jour.');
    }

    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/../includes/admin-header.php';

$success = flash('success');
$admins = $db->query("SELECT * FROM admins ORDER BY login")->fetchAll();

$api_settings = [];
$api_keys = ['payplug_test_secret','payplug_test_public','payplug_live_secret','payplug_live_public','payplug_mode','packlink_api'];
$placeholders = implode(',', array_fill(0, count($api_keys), '?'));
$stmt = $db->prepare("SELECT key, value FROM settings WHERE key IN ($placeholders)");
$stmt->execute($api_keys);
foreach ($stmt->fetchAll() as $r) $api_settings[$r['key']] = $r['value'];

$payplug_mode = $api_settings['payplug_mode'] ?? 'test';
?>

<h1 class="text-2xl font-bold mb-6">Paramètres</h1>

<?php if ($success): ?>
    <div class="bg-green-50 text-green-700 p-3 rounded-xl mb-4 text-sm"><?= h($success) ?></div>
<?php endif; ?>

<div class="space-y-6 max-w-2xl">

    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold">PayPlug</h2>
        </div>
        <form method="POST" class="space-y-4" id="api-form">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update_api">

            <div class="flex items-center justify-between bg-gray-50 rounded-xl p-4">
                <div>
                    <span class="text-sm font-medium" id="mode-label"><?= $payplug_mode === 'live' ? 'Mode Live' : 'Mode Test' ?></span>
                    <p class="text-xs text-gray-400 mt-0.5" id="mode-desc"><?= $payplug_mode === 'live' ? 'Paiements réels activés' : 'Paiements de test uniquement' ?></p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="payplug_mode" value="live" class="sr-only peer"
                           id="payplug-mode-toggle" <?= $payplug_mode === 'live' ? 'checked' : '' ?>
                           onchange="togglePayplugMode()">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    <span class="ml-2 text-xs font-medium" id="mode-badge"><?= $payplug_mode === 'live' ? 'LIVE' : 'TEST' ?></span>
                </label>
            </div>

            <div id="test-keys" class="space-y-3 <?= $payplug_mode === 'live' ? 'hidden' : '' ?>">
                <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Clés Test</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé secrète (Test)</label>
                    <input type="text" name="payplug_test_secret" value="<?= h($api_settings['payplug_test_secret'] ?? '') ?>"
                           placeholder="sk_test_..." class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé publique (Test)</label>
                    <input type="text" name="payplug_test_public" value="<?= h($api_settings['payplug_test_public'] ?? '') ?>"
                           placeholder="pk_test_..." class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
                </div>
            </div>

            <div id="live-keys" class="space-y-3 <?= $payplug_mode === 'live' ? '' : 'hidden' ?>">
                <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Clés Live</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé secrète (Live)</label>
                    <input type="text" name="payplug_live_secret" value="<?= h($api_settings['payplug_live_secret'] ?? '') ?>"
                           placeholder="sk_live_..." class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé publique (Live)</label>
                    <input type="text" name="payplug_live_public" value="<?= h($api_settings['payplug_live_public'] ?? '') ?>"
                           placeholder="pk_live_..." class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
                </div>
            </div>

            <hr class="my-2">

            <div>
                <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Packlink</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clé API Packlink</label>
                    <input type="text" name="packlink_api" value="<?= h($api_settings['packlink_api'] ?? '') ?>"
                           placeholder="Votre clé API Packlink" class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm font-mono">
                </div>
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

<script>
function togglePayplugMode() {
    const toggle = document.getElementById('payplug-mode-toggle');
    const isLive = toggle.checked;
    document.getElementById('test-keys').classList.toggle('hidden', isLive);
    document.getElementById('live-keys').classList.toggle('hidden', !isLive);
    document.getElementById('mode-label').textContent = isLive ? 'Mode Live' : 'Mode Test';
    document.getElementById('mode-desc').textContent = isLive ? 'Paiements réels activés' : 'Paiements de test uniquement';
    document.getElementById('mode-badge').textContent = isLive ? 'LIVE' : 'TEST';
    document.getElementById('mode-badge').classList.toggle('text-green-600', isLive);
    document.getElementById('mode-badge').classList.toggle('text-orange-500', !isLive);
}
togglePayplugMode();
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
