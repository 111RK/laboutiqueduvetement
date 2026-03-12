</main>

<!-- Cart Drawer -->
<div id="cart-drawer" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeCart()"></div>
    <div class="absolute right-0 top-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col slide-up">
        <div class="p-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-bold">Mon Panier</h2>
            <button onclick="closeCart()" class="p-1 hover:bg-gray-100 rounded-full">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="cart-items" class="flex-1 overflow-y-auto p-4">
            <p class="text-gray-400 text-center py-8">Votre panier est vide</p>
        </div>
        <div id="cart-footer" class="border-t p-4 hidden">
            <div class="flex justify-between font-bold text-lg mb-4">
                <span>Total</span>
                <span id="cart-total">0,00 €</span>
            </div>
            <a href="checkout.php" class="block w-full bg-primary-600 text-white text-center py-3 rounded-xl font-medium hover:bg-primary-700 transition">
                Commander
            </a>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div id="product-modal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeProductModal()"></div>
    <div class="absolute inset-x-4 bottom-0 top-16 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 md:w-full md:max-w-2xl md:max-h-[85vh] bg-white rounded-t-2xl md:rounded-2xl shadow-2xl flex flex-col overflow-hidden slide-up">
        <div id="modal-content" class="flex-1 overflow-y-auto">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="bg-white border-t mt-8">
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?> — Tous droits réservés</p>
            <div class="flex gap-4">
                <a href="track.php" class="hover:text-primary-600 transition">Suivi de commande</a>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile bottom nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white border-t sm:hidden z-40 safe-area-bottom">
    <div class="flex justify-around py-2">
        <a href="index.php" class="flex flex-col items-center gap-0.5 text-primary-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="text-[10px]">Accueil</span>
        </a>
        <a href="track.php" class="flex flex-col items-center gap-0.5 text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="text-[10px]">Suivi</span>
        </a>
        <button onclick="openCart()" class="flex flex-col items-center gap-0.5 text-gray-400 relative">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
            <span class="text-[10px]">Panier</span>
        </button>
    </div>
</nav>

<script src="assets/js/app.js"></script>
</body>
</html>
