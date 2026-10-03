<?php
/**
 * Vue : annonces enregistrées en favori (GET /membre/favoris).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::favoris()
 * (palier 7.1 — lecture seule, données réelles de la base) :
 *   $favoris : annonces favorites, colonnes du modèle Favori (titre, prix,
 *              type_annonce, quartier, status, categorie_nom, ville_nom,
 *              ajoute_le, annonce_le)
 *   $total   : Favori::compterPourMembre() (nombre total de favoris)
 *
 * La table `photos` est vide (stockage protégé) : le visuel est rendu par le
 * helper dashPhoto(), qui affiche un remplacement neutre. Aucune URL
 * d'image n'est inventée.
 *
 * Aucune requête SQL ici ; toute valeur est échappée à l'affichage.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mes favoris</h2>
        <p class="dash-section-desc">
            <?= (int) $total ?> annonce<?= (int) $total > 1 ? 's' : '' ?> enregistrée<?= (int) $total > 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<?php if ($favoris === []): ?>
    <!-- État vide : le membre n'a enregistré aucun favori -->
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
            <?php
            // Localisation « Ville, Quartier » construite à partir des colonnes
            // réellement présentes (les deux sont NOT NULL en base).
            $localisation = trim(
                (string) ($favori['ville_nom'] ?? '')
                . ', '
                . (string) ($favori['quartier'] ?? '')
            );
            ?>
            <article class="dash-card dash-card--flush overflow-hidden dash-animate-in">
                <?php
                /*
                 * Visuel : la table `photos` est vide (stockage protégé) et
                 * aucune URL externe n'est inventée. dashPhoto(null) affiche
                 * donc un remplacement neutre, en attendant le palier photos.
                 */
                ?>
                <?= dashPhoto(null, (string) ($favori['titre'] ?? '')) ?>

                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">
                            <?= $esc($favori['categorie_nom'] ?? '') ?>
                        </p>
                        <?= dashBadgeStatut((string) ($favori['status'] ?? '')) ?>
                    </div>

                    <h3 class="mt-1 font-poppins text-sm font-semibold text-stone-900">
                        <?= $esc($favori['titre'] ?? '') ?>
                    </h3>

                    <p class="mt-2 font-poppins text-base font-bold text-stone-900">
                        <?= $esc(dashPrixAffiche($favori['prix'] ?? null, (string) ($favori['type_annonce'] ?? ''))) ?>
                    </p>

                    <p class="mt-1 text-xs text-stone-500">
                        <?= $esc($localisation) ?>
                    </p>

                    <p class="mt-1 text-xs text-stone-400">
                        Ajouté <?= $esc(dashTempsRelatif($favori['ajoute_le'] ?? null)) ?>
                    </p>

                    <div class="dash-actions mt-3">
                        <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                            <?= dashIcon('oeil') ?><span>Voir l'annonce</span>
                        </button>
                        <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                            <?= dashIcon('poubelle') ?><span>Retirer</span>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
