<?php
require_once __DIR__ . '/includes/config.php';
$page_title = 'Politique de confidentialité';
$page_description = 'Politique de confidentialité de La Boutique du Vêtement - Protection de vos données personnelles.';
$page_canonical = 'politique-confidentialite.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Politique de confidentialité</h1>

    <div class="space-y-8 text-gray-700 text-sm leading-relaxed">

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">1. Responsable du traitement</h2>
            <p>Le responsable du traitement des données personnelles est :</p>
            <ul class="mt-2 space-y-1">
                <li><strong>Nom :</strong> Thomas Albert</li>
                <li><strong>Adresse :</strong> 13 Rue de l'Arbizon, 31210 Montréjeau, France</li>
                <li><strong>Email :</strong> contact@laboutiqueduvetement.fr</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">2. Données collectées</h2>
            <p>Dans le cadre de l'utilisation du site <?= SITE_NAME ?>, nous pouvons être amenés à collecter les données suivantes :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li>Nom et prénom</li>
                <li>Adresse email</li>
                <li>Numéro de téléphone</li>
                <li>Adresse postale de livraison</li>
                <li>Données de navigation (cookies techniques)</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">3. Finalité du traitement</h2>
            <p>Les données personnelles collectées sont utilisées pour :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li>Le traitement et le suivi de vos commandes</li>
                <li>La livraison de vos achats</li>
                <li>La gestion de la relation client et du service après-vente</li>
                <li>Le respect de nos obligations légales et réglementaires</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">4. Base légale du traitement</h2>
            <p>Le traitement de vos données personnelles est fondé sur :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li><strong>L'exécution du contrat</strong> : traitement de vos commandes et livraison</li>
                <li><strong>L'obligation légale</strong> : conservation des factures et données comptables</li>
                <li><strong>L'intérêt légitime</strong> : amélioration de nos services et prévention de la fraude</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">5. Destinataires des données</h2>
            <p>Vos données personnelles peuvent être transmises aux prestataires suivants, strictement nécessaires à l'exécution des services :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li><strong>PayPlug</strong> : prestataire de paiement sécurisé</li>
                <li><strong>Packlink</strong> : prestataire d'expédition et de livraison</li>
                <li><strong>o2switch</strong> : hébergeur du site</li>
            </ul>
            <p class="mt-2">Aucune donnée n'est transférée en dehors de l'Union européenne.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">6. Durée de conservation</h2>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li><strong>Données de commande</strong> : 5 ans à compter de la commande (obligation comptable)</li>
                <li><strong>Données de compte client</strong> : 3 ans à compter de la dernière activité</li>
                <li><strong>Cookies</strong> : 13 mois maximum</li>
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">7. Vos droits</h2>
            <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez des droits suivants :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li><strong>Droit d'accès</strong> : obtenir la confirmation que des données vous concernant sont traitées et en obtenir une copie</li>
                <li><strong>Droit de rectification</strong> : demander la correction de données inexactes</li>
                <li><strong>Droit à l'effacement</strong> : demander la suppression de vos données</li>
                <li><strong>Droit à la limitation</strong> : demander la limitation du traitement de vos données</li>
                <li><strong>Droit à la portabilité</strong> : recevoir vos données dans un format structuré</li>
                <li><strong>Droit d'opposition</strong> : vous opposer au traitement de vos données</li>
            </ul>
            <p class="mt-2">Pour exercer vos droits, contactez-nous à : <strong>contact@laboutiqueduvetement.fr</strong></p>
            <p class="mt-2">En cas de litige, vous pouvez introduire une réclamation auprès de la CNIL (Commission Nationale de l'Informatique et des Libertés) : <strong>www.cnil.fr</strong></p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">8. Cookies</h2>
            <p>Le site utilise uniquement des cookies techniques strictement nécessaires au fonctionnement du site :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li><strong>Cookie de session</strong> : gestion du panier d'achat et de la navigation</li>
                <li><strong>Cookie CSRF</strong> : sécurité des formulaires</li>
            </ul>
            <p class="mt-2">Ces cookies ne nécessitent pas votre consentement car ils sont indispensables au fonctionnement du site. Aucun cookie publicitaire ou de suivi n'est utilisé.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">9. Sécurité</h2>
            <p>Nous mettons en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données personnelles contre tout accès non autorisé, modification, divulgation ou destruction. Les paiements sont sécurisés par PayPlug, certifié PCI-DSS. Aucune donnée bancaire n'est stockée sur nos serveurs.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">10. Modification de la politique</h2>
            <p>Nous nous réservons le droit de modifier la présente politique de confidentialité à tout moment. La version en vigueur est celle accessible sur le site.</p>
            <p class="mt-2"><strong>Dernière mise à jour :</strong> <?= date('d/m/Y') ?></p>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
