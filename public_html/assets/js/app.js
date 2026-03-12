function filterCategory(slug) {
    document.querySelectorAll('.category-pill').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.cat === slug);
    });

    document.querySelectorAll('.product-card').forEach(card => {
        if (slug === 'all' || card.dataset.category === slug) {
            card.style.display = '';
            card.classList.add('fade-in');
        } else {
            card.style.display = 'none';
        }
    });
}

const searchInput = document.getElementById('search-input');
if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.product-card').forEach(card => {
                const title = card.querySelector('h3').textContent.toLowerCase();
                const cat = card.querySelector('.product-category')?.textContent?.toLowerCase() || '';
                card.style.display = (!q || title.includes(q) || cat.includes(q)) ? '' : 'none';
            });
        }, 200);
    });
}

let selectedVariationId = null;
let selectedColorId = 0;
let currentProductColors = [];

function openProduct(id) {
    const modal = document.getElementById('product-modal');
    const content = document.getElementById('modal-content');
    content.innerHTML = '<div class="flex items-center justify-center py-12"><div class="animate-spin w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full"></div></div>';
    modal.classList.remove('hidden');
    selectedVariationId = null;
    selectedColorId = 0;

    fetch('api_product.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = '<div class="p-6 text-center text-red-500">' + data.error + '</div>';
                return;
            }

            currentProductColors = data.colors || [];

            let colorsHtml = '';
            if (currentProductColors.length) {
                colorsHtml = '<div class="mb-3"><p class="text-xs font-medium text-gray-500 uppercase mb-2">Couleur</p><div class="flex flex-wrap gap-2" id="color-buttons">';
                currentProductColors.forEach((c, i) => {
                    colorsHtml += `<button onclick="selectColor(this, ${c.id})" class="color-btn w-9 h-9 rounded-full border-3 ${i === 0 ? 'border-primary-500 ring-2 ring-primary-200' : 'border-gray-200'} hover:scale-110 transition-transform" style="background-color:${c.hex_code}" title="${c.name}" data-cid="${c.id}"></button>`;
                });
                colorsHtml += '</div></div>';
                selectedColorId = currentProductColors[0].id;
            }

            let sizesHtml = '';
            if (data.variations && data.variations.length) {
                const adults = data.variations.filter(v => v.size_type === 'adult');
                const children = data.variations.filter(v => v.size_type === 'child');

                if (adults.length) {
                    sizesHtml += '<div class="mb-2"><p class="text-xs font-medium text-gray-500 uppercase mb-1.5">Adulte</p><div class="flex flex-wrap gap-1.5">';
                    adults.forEach(v => {
                        sizesHtml += `<button onclick="selectSize(this,${v.id},${v.price})" class="size-btn px-3 py-1.5 border-2 border-gray-200 rounded-lg text-xs font-medium hover:border-primary-500 transition">${v.size_label} <span class="text-gray-400">${formatPrice(v.price)}</span></button>`;
                    });
                    sizesHtml += '</div></div>';
                }
                if (children.length) {
                    sizesHtml += '<div class="mb-2"><p class="text-xs font-medium text-gray-500 uppercase mb-1.5">Enfant</p><div class="flex flex-wrap gap-1.5">';
                    children.forEach(v => {
                        sizesHtml += `<button onclick="selectSize(this,${v.id},${v.price})" class="size-btn px-3 py-1.5 border-2 border-gray-200 rounded-lg text-xs font-medium hover:border-primary-500 transition">${v.size_label} <span class="text-gray-400">${formatPrice(v.price)}</span></button>`;
                    });
                    sizesHtml += '</div></div>';
                }
            }

            content.innerHTML = `
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-2/5 aspect-square bg-gray-100 flex-shrink-0 relative">
                        <button onclick="closeProductModal()" class="absolute top-2 right-2 z-10 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center shadow hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        ${data.image_url
                            ? `<img src="${data.image_url}" alt="${data.title}" class="w-full h-full object-cover">`
                            : '<div class="w-full h-full flex items-center justify-center text-gray-300"><svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>'}
                    </div>
                    <div class="md:w-3/5 p-4 flex flex-col">
                        ${data.category_name ? `<span class="text-xs text-gray-400 uppercase tracking-wide">${data.category_name}</span>` : ''}
                        <h2 class="text-lg font-bold mt-0.5 mb-1">${data.title}</h2>
                        ${data.description ? `<p class="text-gray-500 text-xs mb-3">${data.description}</p>` : ''}

                        ${colorsHtml}

                        <div class="mb-3">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1.5">Taille</p>
                            ${sizesHtml}
                        </div>

                        <div class="mt-auto pt-2 flex items-center gap-3">
                            <span id="selected-price" class="text-xl font-bold text-primary-700 hidden"></span>
                            <button id="add-to-cart-btn" onclick="addToCart()" disabled
                                    class="flex-1 bg-primary-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-primary-700 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                                Ajouter au panier
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
}

function selectColor(btn, colorId) {
    document.querySelectorAll('.color-btn').forEach(b => {
        b.classList.remove('border-primary-500', 'ring-2', 'ring-primary-200');
        b.classList.add('border-gray-200');
    });
    btn.classList.remove('border-gray-200');
    btn.classList.add('border-primary-500', 'ring-2', 'ring-primary-200');
    selectedColorId = colorId;
}

function selectSize(btn, variationId, price) {
    document.querySelectorAll('.size-btn').forEach(b => {
        b.classList.remove('border-primary-500', 'bg-primary-50');
        b.classList.add('border-gray-200');
    });
    btn.classList.remove('border-gray-200');
    btn.classList.add('border-primary-500', 'bg-primary-50');

    selectedVariationId = variationId;
    const priceEl = document.getElementById('selected-price');
    priceEl.textContent = formatPrice(price);
    priceEl.classList.remove('hidden');
    document.getElementById('add-to-cart-btn').disabled = false;
}

function closeProductModal() {
    document.getElementById('product-modal').classList.add('hidden');
    selectedVariationId = null;
    selectedColorId = 0;
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-backdrop')) {
        closeProductModal();
    }
});

function addToCart() {
    if (!selectedVariationId) return;

    const btn = document.getElementById('add-to-cart-btn');
    btn.disabled = true;
    btn.innerHTML = '<div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>';

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('variation_id', selectedVariationId);
    formData.append('color_id', selectedColorId);
    formData.append('qty', 1);

    fetch('api_cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            updateCartBadge(data.count);
            closeProductModal();
            openCart();
        });
}

function showCartNotification(count) {
        let toast = document.getElementById('cart-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'cart-toast';
        toast.className = 'fixed bottom-4 left-1/2 -translate-x-1/2 bg-gray-800 text-white px-5 py-3 rounded-xl shadow-lg z-50 flex items-center gap-3 transition-all duration-300';
        document.body.appendChild(toast);
    }
    toast.innerHTML = `
        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>Article ajouté au panier (${count})</span>
        <button onclick="openCart()" class="ml-2 underline text-sm">Voir</button>
    `;
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
    }, 3000);
}

function openCart() {
    const drawer = document.getElementById('cart-drawer');
    drawer.classList.remove('translate-x-full');
    loadCart();
}

function closeCart() {
    document.getElementById('cart-drawer').classList.add('translate-x-full');
}

function loadCart() {
    fetch('api_cart.php?action=get')
        .then(r => r.json())
        .then(data => renderCart(data));
}

function renderCart(data) {
    const container = document.getElementById('cart-items');
    const footer = document.getElementById('cart-footer');

    if (!data.items.length) {
        container.innerHTML = '<p class="text-gray-400 text-center py-8">Votre panier est vide</p>';
        footer.classList.add('hidden');
        updateCartBadge(0);
        return;
    }

    let html = '';
    data.items.forEach((item, idx) => {
        const key = data.keys[idx];
        const imgHtml = item.image
            ? `<img src="uploads/products/${item.image}" class="w-full h-full object-cover rounded-lg">`
            : '<div class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center text-gray-400 text-xs">IMG</div>';

        html += `
            <div class="flex gap-3 mb-3 pb-3 border-b last:border-0">
                <div class="w-14 h-14 flex-shrink-0">${imgHtml}</div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm truncate">${item.title}</p>
                    <p class="text-xs text-gray-400">
                        ${item.size_label}${item.color_name ? ' · ' + item.color_name : ''}
                    </p>
                    <div class="flex items-center justify-between mt-1.5">
                        <div class="flex items-center gap-1 bg-gray-100 rounded-lg text-xs">
                            <button onclick="updateCartItem('${key}', ${item.qty - 1})" class="px-2 py-1 hover:bg-gray-200 rounded-l-lg font-bold">−</button>
                            <span class="px-2 font-medium">${item.qty}</span>
                            <button onclick="updateCartItem('${key}', ${item.qty + 1})" class="px-2 py-1 hover:bg-gray-200 rounded-r-lg font-bold">+</button>
                        </div>
                        <span class="text-primary-700 font-bold text-sm">${formatPrice(item.price * item.qty)}</span>
                    </div>
                </div>
                <button onclick="removeCartItem('${key}')" class="text-gray-300 hover:text-red-500 self-start mt-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        `;
    });

    container.innerHTML = html;
    footer.classList.remove('hidden');
    document.getElementById('cart-total').textContent = data.total_formatted;
    updateCartBadge(data.count);
}

function updateCartItem(key, qty) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('key', key);
    formData.append('qty', qty);

    fetch('api_cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => renderCart(data));
}

function removeCartItem(key) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('key', key);

    fetch('api_cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => renderCart(data));
}

function updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',') + ' €';
}
