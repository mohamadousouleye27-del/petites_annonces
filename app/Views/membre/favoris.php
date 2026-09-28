<?php
/**
 * Vue : annonces enregistrées en favori (GET /membre/favoris).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::favoris() :
 *   $favoris : annonces favorites (titre, categorie, localisation, prix,
 *              suffixe, date, image)
 *
 * Données statiques (étape 2) : aucun accès base de données.
 * Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- Avertissement de démonstration -->
<div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
    <span class="mt-0.5 text-amber-500">
        <?= dashIcon('signalements') ?>
    </span>
    <p class="text-sm text-stone-700">
        <span class="font-semibold text-stone-900">Liste de démonstration.</span>
        Vos favoris réels (table <code>favoris</code>) seront chargés à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mes favoris</h2>
        <p class="dash-section-desc">
            <?= count($favoris) ?> annonce<?= count($favoris) > 1 ? 's' : '' ?> enregistrée<?= count($favoris) > 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<?php if ($favoris === []): ?>
    <!-- État vide (non atteint avec les données de démonstration) -->
    <div class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucun favori</p>
            <p>Enregistrez une annonce pour la retrouver ici.</p>
        </div>
    </div>
<?php else: ?>
    <!-- Grille des favoris -->
    <div class="dash-grid dash-grid--cards">
        <?php foreach ($favoris as $favori): ?>
            <article class="dash-card dash-card--flush overflow-hidden dash-animate-in">
                <?php
                /*
                 * Image distante (Unsplash) : like the homepage. L'URL est
                 * échappée avant d'être placée dans l'attribut src.
                 */
                ?>
                <img
                    class="h-44 w-full object-cover"
                    src="<?= $esc($favori['image'] ?? '') ?>"
                    alt=""
                    loading="lazy"
                >

                <div class="p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">
                        <?= $esc($favori['categorie'] ?? '') ?>
                    </p>
                    <h3 class="mt-1 font-poppins text-sm font-semibold text-stone-900">
                        <?= $esc($favori['titre'] ?? '') ?>
                    </h3>

                    <p class="mt-2 font-poppins text-base font-bold text-stone-900">
                        <?= $esc(formatFcfa($favori['prix'] ?? null)) ?><?= $esc($favori['suffixe'] ?? '') ?>
                    </p>

                    <p class="mt-1 text-xs text-stone-500">
                        <?= $esc($favori['localisation'] ?? '') ?>
                    </p>

                    <p class="mt-1 text-xs text-stone-400">
                        <?= $esc($favori['date'] ?? '') ?>
                    </p>

                    <div class="dash-actions mt-3">
                        <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Disponible à l'étape 7">
                            <?= dashIcon('oeil') ?><span>Voir l'annonce</span>
                        </button>
                        <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Disponible à l'étape 7">
                            <?= dashIcon('poubelle') ?><span>Retirer</span>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
