<?php
/**
 * Vue : liste des annonces du membre (GET /membre/annonces).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::annonces() :
 *   $annonces  : annonces du membre (titre, categorie, type, prix, suffixe,
 *                statut, vues, favoris, date)
 *   $compteurs : nombre d'annonces par statut (total, active, en_attente,
 *                expirée, suspendue)
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
        Vos annonces réelles seront chargées depuis la base de données à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mes annonces</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['total'] ?> annonce<?= ((int) $compteurs['total']) > 1 ? 's' : '' ?>
            au total, dont <?= (int) $compteurs['active'] ?> en ligne
        </p>
    </div>

    <button
        type="button"
        class="dash-btn dash-btn--primary"
        disabled
        title="La publication sera activée à l'étape 7 (données dynamiques)"
    >
        <?= dashIcon('plus') ?>
        <span>Publier une annonce</span>
    </button>
</div>

<!-- Compteurs par statut -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="dash-count">Total <strong class="ml-1"><?= (int) $compteurs['total'] ?></strong></span>
    <span class="dash-badge dash-badge--actif"><?= (int) $compteurs['active'] ?> active(s)</span>
    <span class="dash-badge dash-badge--attente"><?= (int) $compteurs['en_attente'] ?> en attente</span>
    <span class="dash-badge dash-badge--expire"><?= (int) $compteurs['expirée'] ?> expirée(s)</span>
    <span class="dash-badge dash-badge--suspendu"><?= (int) $compteurs['suspendue'] ?> suspendue(s)</span>
</div>

<!-- Tableau des annonces -->
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
                                <?= $esc($annonce['categorie'] ?? '') ?> ·
                                <?= $esc($annonce['type'] ?? '') ?>
                            </span>
                        </td>
                        <td class="whitespace-nowrap">
                            <?= $esc(formatFcfa($annonce['prix'] ?? null)) ?><?= $esc($annonce['suffixe'] ?? '') ?>
                        </td>
                        <td><?= dashBadgeStatut((string) ($annonce['statut'] ?? '')) ?></td>
                        <td><?= $esc(number_format((int) ($annonce['vues'] ?? 0), 0, ',', ' ')) ?></td>
                        <td><?= (int) ($annonce['favoris'] ?? 0) ?></td>
                        <td class="whitespace-nowrap text-stone-500"><?= $esc($annonce['date'] ?? '') ?></td>
                        <td>
                            <div class="dash-actions">
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible à l'étape 7">
                                    <?= dashIcon('oeil') ?><span>Voir</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible à l'étape 7">
                                    <?= dashIcon('crayon') ?><span>Modifier</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible à l'étape 7">
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
