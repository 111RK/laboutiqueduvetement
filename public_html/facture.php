<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$order_ref = trim($_GET['ref'] ?? $_POST['ref'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

$db = getDB();
$order = null;
$items = [];
$error = '';

if ($order_ref && $email) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_ref = ? AND customer_email = ? AND payment_status = 'paid'");
    $stmt->execute([$order_ref, $email]);
    $order = $stmt->fetch();

    if (!$order) {
        $error = 'Commande introuvable. Vérifiez votre référence et email.';
    } else {
        $items = json_decode($order['items_json'], true) ?: [];
    }
}

// If download requested and order found, output print-ready HTML
if ($order && isset($_GET['print'])) {
    $name_parts = explode(' ', $order['customer_name'], 2);
    $firstname = $name_parts[0];
    $lastname = $name_parts[1] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?= h($order['order_ref']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica Neue', Arial, sans-serif; }
        body { padding: 40px; color: #1a1a1a; font-size: 13px; line-height: 1.5; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo { font-size: 20px; font-weight: 700; color: #0d9488; }
        .logo-sub { font-size: 11px; color: #888; margin-top: 4px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 28px; font-weight: 700; color: #0d9488; text-transform: uppercase; letter-spacing: 2px; }
        .invoice-title p { color: #888; font-size: 12px; margin-top: 4px; }
        .addresses { display: flex; gap: 40px; margin-bottom: 30px; }
        .address-block { flex: 1; }
        .address-block h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 8px; font-weight: 600; }
        .address-block p { font-size: 13px; }
        .meta { display: flex; gap: 30px; margin-bottom: 30px; padding: 15px 0; border-top: 2px solid #0d9488; border-bottom: 1px solid #eee; }
        .meta-item { }
        .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; }
        .meta-value { font-size: 14px; font-weight: 600; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        thead th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600; padding: 10px 8px; border-bottom: 2px solid #eee; }
        thead th:last-child { text-align: right; }
        tbody td { padding: 12px 8px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        tbody td:last-child { text-align: right; font-weight: 600; }
        .totals { margin-left: auto; width: 280px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
        .totals-row.total { border-top: 2px solid #0d9488; margin-top: 8px; padding-top: 12px; font-size: 18px; font-weight: 700; color: #0d9488; }
        .footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 11px; color: #888; }
        .footer p { margin-bottom: 4px; }
        @media print {
            body { padding: 20px; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="logo"><?= SITE_NAME ?></div>
            <div class="logo-sub">Thomas Albert<br>13 Rue de l'Arbizon<br>31210 Montréjeau<br>contact@laboutiqueduvetement.fr</div>
        </div>
        <div class="invoice-title">
            <h1>Facture</h1>
            <p><?= h($order['order_ref']) ?></p>
        </div>
    </div>

    <div class="addresses">
        <div class="address-block">
            <h3>Facturer à</h3>
            <p>
                <strong><?= h($order['customer_name']) ?></strong><br>
                <?= h($order['customer_address']) ?><br>
                <?= h($order['customer_zipcode']) ?> <?= h($order['customer_city']) ?>
                <?php if (!empty($order['customer_country']) && $order['customer_country'] !== 'FR'): ?>
                    <br><?= h($order['customer_country']) ?>
                <?php endif; ?>
                <br><?= h($order['customer_email']) ?>
                <?php if ($order['customer_phone']): ?><br><?= h($order['customer_phone']) ?><?php endif; ?>
            </p>
        </div>
        <?php if (!empty($order['relay_point_name'])): ?>
        <div class="address-block">
            <h3>Livrer à (Point relais)</h3>
            <p>
                <strong><?= h($order['relay_point_name']) ?></strong><br>
                <?= h($order['relay_point_address']) ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">Date</div>
            <div class="meta-value"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Référence</div>
            <div class="meta-value"><?= h($order['order_ref']) ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Livraison</div>
            <div class="meta-value"><?= h($order['shipping_method'] ?: 'Standard') ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Paiement</div>
            <div class="meta-value" style="color: #16a34a;">Payé</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th>Détails</th>
                <th>Qté</th>
                <th>Prix unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><strong><?= h($item['title']) ?></strong></td>
                <td>
                    Taille : <?= h($item['size_label']) ?>
                    <?= !empty($item['color_name']) ? ' | Couleur : ' . h($item['color_name']) : '' ?>
                </td>
                <td><?= (int)$item['qty'] ?></td>
                <td><?= format_price($item['price']) ?></td>
                <td><?= format_price($item['price'] * $item['qty']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Sous-total HT</span>
            <span><?= format_price($order['subtotal']) ?></span>
        </div>
        <div class="totals-row">
            <span>TVA (non applicable, art. 293B CGI)</span>
            <span>0,00 €</span>
        </div>
        <div class="totals-row">
            <span>Frais de livraison</span>
            <span><?= format_price($order['shipping_cost']) ?></span>
        </div>
        <div class="totals-row total">
            <span>Total TTC</span>
            <span><?= format_price($order['total']) ?></span>
        </div>
    </div>

    <div class="footer">
        <p><strong><?= SITE_NAME ?></strong> — Thomas Albert — Auto-entrepreneur</p>
        <p>13 Rue de l'Arbizon, 31210 Montréjeau — contact@laboutiqueduvetement.fr</p>
        <p>TVA non applicable, article 293B du CGI</p>
    </div>

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>* { font-family: 'Inter', sans-serif; }</style>
    <script>
        tailwind.config = { theme: { extend: { colors: {
            primary: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' }
        }}}}
    </script>
</head>
<body class="bg-gray-50 min-h-screen">

<header class="bg-white shadow-sm">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center gap-3">
        <a href="/" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-lg font-bold">Télécharger ma facture</h1>
    </div>
</header>

<div class="max-w-md mx-auto px-4 py-8">
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <p class="text-red-600 text-sm"><?= h($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-lg font-bold mb-1">Commande <?= h($order['order_ref']) ?></h2>
            <p class="text-gray-500 text-sm mb-6">Total : <strong class="text-primary-700"><?= format_price($order['total']) ?></strong></p>

            <a href="facture?ref=<?= urlencode($order_ref) ?>&email=<?= urlencode($email) ?>&print=1" target="_blank"
               class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-3 rounded-xl font-medium hover:bg-primary-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Télécharger la facture (PDF)
            </a>
            <p class="text-xs text-gray-400 mt-3">La facture s'ouvrira dans un nouvel onglet pour impression/PDF</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="text-center mb-6">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-gray-500 text-sm">Renseignez vos informations pour accéder à votre facture</p>
            </div>
            <form method="GET" class="space-y-3">
                <input type="text" name="ref" placeholder="Référence commande (ex: LBV-XXXXXXXX)" required
                       value="<?= h($order_ref) ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <input type="email" name="email" placeholder="Adresse email utilisée" required
                       value="<?= h($email) ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <button type="submit"
                        class="w-full bg-primary-600 text-white py-2.5 rounded-xl font-medium hover:bg-primary-700 transition">
                    Rechercher ma facture
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
