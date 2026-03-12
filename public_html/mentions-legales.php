<?php
require_once __DIR__ . '/includes/config.php';
$page_title = 'Mentions légales';
$page_description = 'Mentions légales de La Boutique du Vêtement - Informations légales, éditeur du site et hébergeur.';
$page_canonical = 'mentions-legales.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Mentions légales</h1>

    <div class="space-y-8 text-gray-700 text-sm leading-relaxed">

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">1. Éditeur du site</h2>
            <p>Le site <?= SITE_NAME ?> est édité par :</p>
            <ul class="mt-2 space-y-1">
                <li><strong>Nom :</strong> Thomas Albert</li>
                <li><strong>Adresse :</strong> 13 Rue de l'Arbizon, 31210 Montréjeau, France</li>
                <li><strong>Email :</strong> contaact@laboutiqueduvetement.fr</li>
                <li><strong>Statut :</strong> Entrepreneur individuel</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">2. Hébergeur</h2>
            <p>Le site est hébergé par :</p>
            <ul class="mt-2 space-y-1">
                <li><strong>Nom :</strong> o2switch</li>
                <li><strong>Adresse :</strong> 222-224 Boulevard Gustave Flaubert, 63000 Clermont-Ferrand, France</li>
                <li><strong>Téléphone :</strong> 04 44 44 60 40</li>
                <li><strong>Site web :</strong> www.o2switch.fr</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">3. Propriété intellectuelle</h2>
            <p>L'ensemble des contenus présents sur le site <?= SITE_NAME ?> (textes, images, graphismes, logo, icônes, etc.) sont protégés par les lois françaises et internationales relatives à la propriété intellectuelle.</p>
            <p class="mt-2">Toute reproduction, représentation, modification, publication, transmission ou dénaturation, totale ou partielle, du site ou de son contenu, par quelque procédé que ce soit, et sur quelque support que ce soit, est interdite sans l'autorisation écrite préalable de Thomas Albert.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">4. Responsabilité</h2>
            <p>L'éditeur s'efforce de fournir sur le site des informations aussi précises que possible. Toutefois, il ne pourra être tenu responsable des oublis, inexactitudes ou carences dans la mise à jour, qu'elles soient de son fait ou du fait des tiers partenaires qui lui fournissent ces informations.</p>
            <p class="mt-2">Toutes les informations indiquées sur le site sont données à titre indicatif et sont susceptibles d'évoluer. Les informations présentes sur le site ne sont pas exhaustives. Elles sont données sous réserve de modifications ayant été apportées depuis leur mise en ligne.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">5. Liens hypertextes</h2>
            <p>Le site peut contenir des liens hypertextes vers d'autres sites. Cependant, l'éditeur n'a pas la possibilité de vérifier le contenu des sites ainsi visités et décline donc toute responsabilité quant aux risques éventuels de contenus illicites.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">6. Droit applicable</h2>
            <p>Les présentes mentions légales sont régies par le droit français. En cas de litige, les tribunaux français seront seuls compétents.</p>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
