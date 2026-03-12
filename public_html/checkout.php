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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $country = trim($_POST['country'] ?? 'FR');

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
                customer_address, customer_city, customer_zipcode, customer_country, items_json,
                subtotal, shipping_cost, shipping_method, total, payment_status, order_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'new')
        ");
        $stmt->execute([
            $order_ref, $name, $email, $phone,
            $address, $city, $zipcode, $country, json_encode(array_values($cart)),
            $subtotal, $shipping_cost, $shipping_method, $total
        ]);

        $order_id = $db->lastInsertId();

        $amount = (int)($total * 100);

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
                'country' => $country,
            ],
            'shipping' => [
                'first_name' => explode(' ', $name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $name), 1)) ?: $name,
                'email' => $email,
                'address1' => $address,
                'postcode' => $zipcode,
                'city' => $city,
                'country' => $country,
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

        $pp = get_payplug_keys();

        $ch = curl_init('https://api.payplug.com/v1/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payplug_data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $pp['secret'],
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
                    <select name="country" id="country-field"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="FR" selected>&#127467;&#127479; France</option>
                        <option value="BE">&#127463;&#127466; Belgique</option>
                        <option value="LU">&#127473;&#127482; Luxembourg</option>
                        <option value="DE">&#127465;&#127466; Allemagne</option>
                        <option value="ES">&#127466;&#127480; Espagne</option>
                        <option value="IT">&#127470;&#127481; Italie</option>
                        <option value="PT">&#127477;&#127481; Portugal</option>
                        <option value="NL">&#127475;&#127473; Pays-Bas</option>
                        <option value="AT">&#127462;&#127481; Autriche</option>
                        <option value="CH">&#127464;&#127469; Suisse</option>
                    </select>
                </div>
                <div class="sm:col-span-2 relative">
                    <input type="text" name="address" id="address-field" placeholder="Adresse" required autocomplete="off"
                           value="<?= h($_POST['address'] ?? '') ?>"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <div id="address-suggestions" class="absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-30 hidden max-h-48 overflow-y-auto"></div>
                </div>
                <input type="text" name="zipcode" id="zipcode-field" placeholder="Code postal" required
                       value="<?= h($_POST['zipcode'] ?? '') ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                <input type="text" name="city" id="city-field" placeholder="Ville" required
                       value="<?= h($_POST['city'] ?? '') ?>"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
        </div>

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
    const addressField = document.getElementById('address-field');
    const zipField = document.getElementById('zipcode-field');
    const cityField = document.getElementById('city-field');
    const countryField = document.getElementById('country-field');
    const suggestionsBox = document.getElementById('address-suggestions');
    let addrDebounce;

    addressField.addEventListener('input', function() {
        clearTimeout(addrDebounce);
        const q = this.value.trim();
        if (q.length < 3) { suggestionsBox.classList.add('hidden'); return; }
        addrDebounce = setTimeout(() => fetchAddressSuggestions(q), 300);
    });

    addressField.addEventListener('blur', function() {
        setTimeout(() => suggestionsBox.classList.add('hidden'), 200);
    });

    function fetchAddressSuggestions(query) {
        const country = countryField.value;

        if (country === 'FR') {
            fetch('https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(query) + '&limit=5&autocomplete=1')
                .then(r => r.json())
                .then(data => {
                    if (!data.features || !data.features.length) { suggestionsBox.classList.add('hidden'); return; }
                    let html = '';
                    data.features.forEach(f => {
                        const p = f.properties;
                        html += `<div class="px-4 py-2.5 hover:bg-primary-50 cursor-pointer text-sm border-b last:border-0" onclick="pickAddress('${escAttr(p.name)}','${escAttr(p.postcode || '')}','${escAttr(p.city || '')}')">
                            <p class="font-medium">${esc(p.label)}</p>
                            <p class="text-xs text-gray-400">${esc(p.postcode || '')} ${esc(p.city || '')}</p>
                        </div>`;
                    });
                    suggestionsBox.innerHTML = html;
                    suggestionsBox.classList.remove('hidden');
                })
                .catch(() => suggestionsBox.classList.add('hidden'));
        } else {
            fetch('https://photon.komoot.io/api/?q=' + encodeURIComponent(query) + '&limit=5&lang=fr&osm_tag=place&osm_tag=highway&layer=address&layer=street' +
                '&bbox=-10,35,30,72')
                .then(r => r.json())
                .then(data => {
                    if (!data.features || !data.features.length) { suggestionsBox.classList.add('hidden'); return; }
                    let html = '';
                    data.features.forEach(f => {
                        const p = f.properties;
                        const street = p.street || p.name || '';
                        const houseNum = p.housenumber || '';
                        const addr = houseNum ? houseNum + ' ' + street : street;
                        const zip = p.postcode || '';
                        const city = p.city || p.town || p.village || '';
                        const ctry = p.country || '';
                        html += `<div class="px-4 py-2.5 hover:bg-primary-50 cursor-pointer text-sm border-b last:border-0" onclick="pickAddress('${escAttr(addr)}','${escAttr(zip)}','${escAttr(city)}')">
                            <p class="font-medium">${esc(addr)}</p>
                            <p class="text-xs text-gray-400">${esc(zip)} ${esc(city)} ${esc(ctry)}</p>
                        </div>`;
                    });
                    suggestionsBox.innerHTML = html;
                    suggestionsBox.classList.remove('hidden');
                })
                .catch(() => suggestionsBox.classList.add('hidden'));
        }
    }

    function pickAddress(addr, zip, city) {
        addressField.value = addr;
        zipField.value = zip;
        cityField.value = city;
        suggestionsBox.classList.add('hidden');
        saveCustomerInfo();
        checkShipping();
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function escAttr(s) { return s.replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;'); }

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

                if (data.options.length) {
                    selectShipping(data.options[0].name, data.options[0].price);
                }
            });
    }

    function selectShipping(name, price) {
        document.getElementById('shipping_method').value = name;
        document.getElementById('shipping_cost').value = price;
        updateTotal(price);

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

    const customerFields = ['name', 'email', 'phone', 'address', 'zipcode', 'city', 'country'];
    const storageKey = 'lbv_customer';

    function saveCustomerInfo() {
        const data = {};
        customerFields.forEach(f => {
            const el = document.querySelector(`[name="${f}"]`);
            if (el) data[f] = el.value;
        });
        localStorage.setItem(storageKey, JSON.stringify(data));
    }

    function loadCustomerInfo() {
        const raw = localStorage.getItem(storageKey);
        if (!raw) return;
        try {
            const data = JSON.parse(raw);
            customerFields.forEach(f => {
                const el = document.querySelector(`[name="${f}"]`);
                if (el && data[f] && !el.value) {
                    el.value = data[f];
                }
            });
            if (zipField.value.length >= 4 && cityField.value.length >= 2) {
                fetchShipping();
            }
        } catch(e) {}
    }

    customerFields.forEach(f => {
        const el = document.querySelector(`[name="${f}"]`);
        if (el) el.addEventListener('change', saveCustomerInfo);
        if (el && el.tagName !== 'SELECT') el.addEventListener('input', saveCustomerInfo);
    });

    loadCustomerInfo();
</script>
</body>
</html>
