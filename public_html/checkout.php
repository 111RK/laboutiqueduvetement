<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: index.php');
    exit;
}

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
}

$errors = [];
$shipping_options = [];

// If form submitted, process order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');

    if (!$name) $errors[] = 'Nom requis';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email valide requis';
    if (!$address) $errors[] = 'Adresse requise';
    if (!$city) $errors[] = 'Ville requise';
    if (!$zipcode) $errors[] = 'Code postal requis';

    $shipping_method = $_POST['shipping_method'] ?? '';
    $shipping_cost = (float)($_POST['shipping_cost'] ?? 0);

    if (empty($errors)) {
        $total = $subtotal + $shipping_cost;
        $order_ref = 'LBV-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO orders (order_ref, customer_name, customer_email, customer_phone,
                customer_address, customer_city, customer_zipcode, items_json,
                subtotal, shipping_cost, shipping_method, total, payment_status, order_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'new')
        ");
        $stmt->execute([
            $order_ref, $name, $email, $phone,
            $address, $city, $zipcode, json_encode(array_values($cart)),
            $subtotal, $shipping_cost, $shipping_method, $total
        ]);

        $order_id = $db->lastInsertId();

        // Create PayPlug payment
        $amount = (int)($total * 100); // PayPlug uses cents

        $payplug_data = [
            'amount' => $amount,
            'currency' => 'EUR',
            'billing' => [
                'first_name' => explode(' ', $name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $name), 1)) ?: $name,
                'email' => $email,
                'address1' => $address,
                'postcode' => $zipcode,
                'city' => $city,
                'country' => 'FR',
            ],
            'shipping' => [
                'first_name' => explode(' ', $name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $name), 1)) ?: $name,
                'email' => $email,
                'address1' => $address,
                'postcode' => $zipcode,
                'city' => $city,
                'country' => 'FR',
            ],
            'hosted_payment' => [
                'return_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payment_return.php?order=' . $order_ref,
                'cancel_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/checkout.php',
            ],
            'notification_url' => (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/payment_webhook.php',
            'metadata' => [
                'order_id' => $order_id,
                'order_ref' => $order_ref,
            ],
        ];

        $api_url = PAYPLUG_TEST_MODE
            ? 'https://api.payplug.com/v1/payments'
            : 'https://api.payplug.com/v1/payments';

        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payplug_data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . PAYPLUG_SECRET_KEY,
                'Content-Type: application/json',
                'PayPlug-Version: 2019-08-06',
            ],
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 201) {
            $payment = json_decode($response, true);
            $payment_id = $payment['id'] ?? '';

            $stmt = $db->prepare("UPDATE orders SET payment_id = ? WHERE id = ?");
            $stmt->execute([$payment_id, $order_id]);

            // Redirect to PayPlug hosted payment page
            $payment_url = $payment['hosted_payment']['payment_url'] ?? '';
            if ($payment_url) {
                $_SESSION['cart'] = [];
                header('Location: ' . $payment_url);
                exit;
            }
        }

        $errors[] = 'Erreur lors de la création du paiement. Veuillez réessayer.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - <?= SITE_NAME ?></title>
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
        <a href="index.php" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-lg font-bold">Finaliser la commande</h1>
    </div>
</header>

<div class="max-w-3xl mx-auto px-4 py-6">
    <?php if ($errors): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <?php foreach ($errors as $e): ?>
                <p class="text-red-600 text-sm"><?= h($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="checkout-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- Order Summary -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm">
            <h2 class="font-bold mb-3">Récapitulatif</h2>
            <?php foreach ($cart as $item): ?>
                <div class="flex justify-between items-center py-2 border-b last:border-0">
                    <div>
                        <p class="text-sm font-medium"><?= h($item['title']) ?></p>
                        <p class="text-xs text-gray-400">Taille : <?= h($item['size_label']) ?> × <?= $item['qty'] ?></p>
                    </div>
                    <span class="font-medium text-sm"><?= format_price($item['price'] * $item['qty']) ?></span>
                </div>
            <?php endforeach; ?>
            <div class="flex justify-between mt-3 pt-2 border-t font-bold">
                <span>Sous-total</span>
                <span><?= format_price($subtotal) ?></span>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm">
            <h2 class="font-bold mb-3">Vos informations</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2">
                    <input type="text" name="name" placeholder="Nom complet" required
                           value="<?= h($_POST['name'] ?? '') ?>"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <input type="email" name="email" placeholder="Email" required
                       value="<?= h($_POST['email'] ?? '') ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <input type="tel" name="phone" placeholder="Téléphone"
                       value="<?= h($_POST['phone'] ?? '') ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <div class="sm:col-span-2">
                    <input type="text" name="address" placeholder="Adresse" required
                           value="<?= h($_POST['address'] ?? '') ?>"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <input type="text" name="zipcode" placeholder="Code postal" required
                       value="<?= h($_POST['zipcode'] ?? '') ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <input type="text" name="city" placeholder="Ville" required
                       value="<?= h($_POST['city'] ?? '') ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
        </div>

        <!-- Shipping Options (Packlink) -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm">
            <h2 class="font-bold mb-3">Mode de livraison</h2>
            <div id="shipping-options">
                <p class="text-sm text-gray-400" id="shipping-placeholder">Renseignez votre adresse pour voir les options de livraison</p>
                <div id="shipping-list" class="space-y-2 hidden"></div>
                <div id="shipping-loading" class="hidden text-center py-4">
                    <div class="animate-spin w-6 h-6 border-4 border-primary-600 border-t-transparent rounded-full mx-auto"></div>
                </div>
            </div>
            <input type="hidden" name="shipping_method" id="shipping_method" value="">
            <input type="hidden" name="shipping_cost" id="shipping_cost" value="0">
        </div>

        <!-- Total + Pay -->
        <div class="bg-white rounded-xl p-4 mb-4 shadow-sm">
            <div class="flex justify-between items-center text-lg font-bold">
                <span>Total à payer</span>
                <span id="total-display" class="text-primary-700"><?= format_price($subtotal) ?></span>
            </div>
            <button type="submit"
                    class="mt-4 w-full bg-primary-600 text-white py-3 rounded-xl font-medium hover:bg-primary-700 transition">
                Payer par carte bancaire
            </button>
            <p class="text-xs text-gray-400 text-center mt-2">Paiement sécurisé par PayPlug</p>
        </div>
    </form>
</div>

<script>
    // Fetch Packlink shipping options when address is filled
    const zipField = document.querySelector('[name="zipcode"]');
    const cityField = document.querySelector('[name="city"]');
    let shippingDebounce;

    function checkShipping() {
        clearTimeout(shippingDebounce);
        shippingDebounce = setTimeout(fetchShipping, 800);
    }

    if (zipField) zipField.addEventListener('input', checkShipping);
    if (cityField) cityField.addEventListener('input', checkShipping);

    function fetchShipping() {
        const zip = zipField.value.trim();
        const city = cityField.value.trim();
        if (zip.length < 4 || city.length < 2) return;

        document.getElementById('shipping-placeholder').classList.add('hidden');
        document.getElementById('shipping-loading').classList.remove('hidden');
        document.getElementById('shipping-list').classList.add('hidden');

        fetch('api_shipping.php?zipcode=' + encodeURIComponent(zip) + '&city=' + encodeURIComponent(city))
            .then(r => r.json())
            .then(data => {
                document.getElementById('shipping-loading').classList.add('hidden');
                const list = document.getElementById('shipping-list');

                if (data.error || !data.options || !data.options.length) {
                    list.innerHTML = '<p class="text-sm text-gray-500">Livraison standard : 4,99 €</p>';
                    list.classList.remove('hidden');
                    document.getElementById('shipping_method').value = 'standard';
                    document.getElementById('shipping_cost').value = '4.99';
                    updateTotal(4.99);
                    return;
                }

                let html = '';
                data.options.forEach((opt, i) => {
                    html += `
                        <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer hover:border-primary-300 transition ${i === 0 ? 'border-primary-500 bg-primary-50' : 'border-gray-200'}">
                            <input type="radio" name="shipping_radio" value="${i}" ${i === 0 ? 'checked' : ''}
                                   onchange="selectShipping('${opt.name}', ${opt.price})"
                                   class="text-primary-600">
                            <div class="flex-1">
                                <p class="font-medium text-sm">${opt.name}</p>
                                <p class="text-xs text-gray-400">${opt.transit_time || ''}</p>
                            </div>
                            <span class="font-bold text-sm">${parseFloat(opt.price).toFixed(2).replace('.', ',')} €</span>
                        </label>
                    `;
                });
                list.innerHTML = html;
                list.classList.remove('hidden');

                // Auto-select first
                if (data.options.length) {
                    selectShipping(data.options[0].name, data.options[0].price);
                }
            });
    }

    function selectShipping(name, price) {
        document.getElementById('shipping_method').value = name;
        document.getElementById('shipping_cost').value = price;
        updateTotal(price);

        // Update visual
        document.querySelectorAll('#shipping-list label').forEach(l => {
            const radio = l.querySelector('input[type=radio]');
            if (radio.checked) {
                l.classList.add('border-primary-500', 'bg-primary-50');
                l.classList.remove('border-gray-200');
            } else {
                l.classList.remove('border-primary-500', 'bg-primary-50');
                l.classList.add('border-gray-200');
            }
        });
    }

    const subtotal = <?= $subtotal ?>;
    function updateTotal(shippingCost) {
        const total = subtotal + parseFloat(shippingCost);
        document.getElementById('total-display').textContent = total.toFixed(2).replace('.', ',') + ' €';
    }
</script>
</body>
</html>
