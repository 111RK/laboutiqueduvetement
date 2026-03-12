<?php
require_once __DIR__ . '/includes/config.php';
$page_title = 'Conditions Générales de Vente';
$page_description = 'Conditions générales de vente de La Boutique du Vêtement - Commandes, livraison, retours et remboursements.';
$page_canonical = 'cgv.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Conditions Générales de Vente</h1>

    <div class="space-y-8 text-gray-700 text-sm leading-relaxed">

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">1. Objet</h2>
            <p>Les présentes Conditions Générales de Vente (CGV) régissent l'ensemble des ventes effectuées sur le site <?= SITE_NAME ?>, édité par Thomas Albert, situé au 13 Rue de l'Arbizon, 31210 Montréjeau, France.</p>
            <p class="mt-2">Toute commande passée sur le site implique l'acceptation sans réserve des présentes CGV.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">2. Produits</h2>
            <p>Les produits proposés à la vente sont des vêtements (t-shirts, sweats, etc.) pour adultes et enfants. Les photographies et descriptions des produits sont aussi fidèles que possible mais ne peuvent assurer une similitude parfaite avec le produit, notamment en raison des réglages de couleur des écrans.</p>
            <p class="mt-2">Nos produits sont disponibles dans la limite des stocks disponibles.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">3. Prix</h2>
            <p>Les prix sont indiqués en euros (€) toutes taxes comprises (TTC). Ils sont susceptibles d'être modifiés à tout moment, mais les produits seront facturés au prix en vigueur lors de la validation de la commande.</p>
            <p class="mt-2">Les frais de livraison sont indiqués avant la validation définitive de la commande.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">4. Commande</h2>
            <p>Le processus de commande se déroule comme suit :</p>
            <ol class="mt-2 space-y-1 list-decimal list-inside">
                <li>Sélection des produits et ajout au panier</li>
                <li>Vérification du panier</li>
                <li>Renseignement des informations de livraison</li>
                <li>Choix du mode de livraison</li>
                <li>Paiement sécurisé en ligne</li>
                <li>Confirmation de commande par email</li>
            </ol>
            <p class="mt-2">La commande est considérée comme définitive après le paiement intégral du prix. Nous nous réservons le droit de refuser toute commande pour motif légitime.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">5. Paiement</h2>
            <p>Le paiement s'effectue en ligne par carte bancaire via notre prestataire sécurisé PayPlug, certifié PCI-DSS. Les données bancaires sont chiffrées et ne transitent pas par nos serveurs.</p>
            <p class="mt-2">Le paiement est débité au moment de la validation de la commande.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">6. Livraison</h2>
            <p>Les livraisons sont effectuées en France métropolitaine. Les délais de livraison sont indicatifs et varient selon le mode de livraison choisi :</p>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                <li><strong>Colissimo</strong> : 2 à 4 jours ouvrés</li>
                <li><strong>Mondial Relay</strong> : 3 à 5 jours ouvrés</li>
                <li><strong>Chronopost</strong> : 1 à 2 jours ouvrés</li>
            </ul>
            <p class="mt-2">Un numéro de suivi est communiqué dès l'expédition du colis. En cas de retard de livraison, contactez-nous à contact@laboutiqueduvetement.fr.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">7. Droit de rétractation</h2>
            <p>Conformément à l'article L221-18 du Code de la consommation, vous disposez d'un délai de <strong>14 jours calendaires</strong> à compter de la réception de votre commande pour exercer votre droit de rétractation, sans avoir à justifier de motif.</p>
            <p class="mt-2">Pour exercer ce droit, adressez-nous un email à <strong>contact@laboutiqueduvetement.fr</strong> en indiquant votre numéro de commande et votre souhait de rétractation.</p>
            <p class="mt-2">Les articles retournés doivent être dans leur état d'origine, non portés, non lavés, avec leurs étiquettes. Les frais de retour sont à la charge du client.</p>
            <p class="mt-2">Le remboursement sera effectué dans un délai de 14 jours suivant la réception des articles retournés, par le même moyen de paiement que celui utilisé lors de la commande.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">8. Réclamations et garanties</h2>
            <p>Tous nos produits bénéficient de la garantie légale de conformité (articles L217-4 à L217-14 du Code de la consommation) et de la garantie contre les vices cachés (articles 1641 à 1649 du Code civil).</p>
            <p class="mt-2">En cas de produit défectueux ou non conforme, contactez-nous dans les meilleurs délais à <strong>contact@laboutiqueduvetement.fr</strong> avec une photo du défaut et votre numéro de commande.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">9. Protection des données</h2>
            <p>Les données personnelles collectées sont traitées conformément à notre <a href="politique-confidentialite.php" class="text-primary-600 hover:underline">Politique de confidentialité</a> et au RGPD.</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">10. Médiation</h2>
            <p>En cas de litige non résolu à l'amiable, conformément à l'article L612-1 du Code de la consommation, vous pouvez recourir gratuitement au service de médiation de la consommation. Le médiateur compétent est :</p>
            <p class="mt-2"><strong>CNPM - Médiation de la consommation</strong><br>27 Avenue de la Libération, 42400 Saint-Chamond<br>www.cnpm-mediation-consommation.eu</p>
        </section>

        <section>
            <h2 class="text-lg font-bold text-gray-900 mb-3">11. Droit applicable</h2>
            <p>Les présentes CGV sont soumises au droit français. Tout litige relatif à leur interprétation ou leur exécution relève des tribunaux compétents de Toulouse.</p>
            <p class="mt-2"><strong>Dernière mise à jour :</strong> <?= date('d/m/Y') ?></p>
        </section>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
