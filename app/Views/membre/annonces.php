<?php
/**
 * Vue : liste des annonces du membre (GET /membre/annonces).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::annonces()
 * (palier 7.1 — lecture seule, données réelles de la base) :
 *   $annonces  : annonces du membre, colonnes du modèle Annonce (titre,
 *                categorie_nom, type_annonce, prix, ville_nom, quartier,
 *                status, nb_vues, nb_favoris, created_at)
 *   $compteurs : statistiques Annonce::statistiquesMembre() (total, actives,
 *                en_attente, expirees, suspendues, vues)
 *
 * Aucune requête SQL ici. Les valeurs techniques sont mises en forme par les
 * helpers d'affichage (dashTypeAnnonce, dashPrixAffiche, dashBadgeStatut,
 * dashTempsRelatif). Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mes annonces</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['total'] ?> annonce<?= ((int) $compteurs['total']) > 1 ? 's' : '' ?>
            au total, dont <?= (int) $compteurs['actives'] ?> en ligne
        </p>
    </div>

    <button
        type="button"
        class="dash-btn dash-btn--primary"
        disabled
        title="La publication sera disponible dans une prochaine étape"
    >
        <?= dashIcon('plus') ?>
        <span>Publier une annonce</span>
    </button>
</div>

<!-- Compteurs par statut -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="dash-count">Total <strong class="ml-1"><?= (int) $compteurs['total'] ?></strong></span>
    <span class="dash-badge dash-badge--actif"><?= (int) $compteurs['actives'] ?> active(s)</span>
    <span class="dash-badge dash-badge--attente"><?= (int) $compteurs['en_attente'] ?> en attente</span>
    <span class="dash-badge dash-badge--expire"><?= (int) $compteurs['expirees'] ?> expirée(s)</span>
    <span class="dash-badge dash-badge--suspendu"><?= (int) $compteurs['suspendues'] ?> suspendue(s)</span>
</div>

<!-- Tableau des annonces -->
<?php if ($annonces === []): ?>
    <!-- État vide : le membre n'a publié aucune annonce -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune annonce</p>
            <p>Vos annonces publiées apparaîtront ici.</p>
        </div>
    </section>
<?php else: ?>
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Liste des annonces du membre</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Prix</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Vues</th>
                        <th scope="col">Favoris</th>
                        <th scope="col">Publiée</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($annonces as $annonce): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($annonce['titre'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    <?= $esc($annonce['categorie_nom'] ?? '') ?> ·
                                    <?= $esc(dashTypeAnnonce((string) ($annonce['type_annonce'] ?? ''))) ?> ·
                                    <?= $esc($annonce['ville_nom'] ?? '') ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <?= $esc(dashPrixAffiche($annonce['prix'] ?? null, (string) ($annonce['type_annonce'] ?? ''))) ?>
                            </td>
                            <td><?= dashBadgeStatut((string) ($annonce['status'] ?? '')) ?></td>
                            <td><?= $esc(number_format((int) ($annonce['nb_vues'] ?? 0), 0, ',', ' ')) ?></td>
                            <td><?= (int) ($annonce['nb_favoris'] ?? 0) ?></td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashTempsRelatif($annonce['created_at'] ?? null)) ?>
                            </td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                                        <?= dashIcon('oeil') ?><span>Voir</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                                        <?= dashIcon('crayon') ?><span>Modifier</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                                        <?= dashIcon('poubelle') ?><span>Supprimer</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
