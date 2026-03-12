<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $db = getDB();

    $stmt = $db->query("SELECT value FROM settings WHERE key = 'admin_password_hash'");
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['value'])) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }

    $error = 'Mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-lg max-w-sm w-full mx-4">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-teal-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold">Administration</h1>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 text-sm p-3 rounded-xl mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="password" placeholder="Mot de passe" required autofocus
                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 mb-4 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <button type="submit" class="w-full bg-teal-600 text-white py-2.5 rounded-xl font-medium hover:bg-teal-700 transition">
                Se connecter
            </button>
        </form>
    </div>
</body>
</html>
