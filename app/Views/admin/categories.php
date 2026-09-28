<?php
/**
 * Vue : référentiel des catégories (GET /admin/categories).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::categories() :
 *   $categories : lignes du référentiel (nom, parent, statut, annonces)
 *   $compteurs  : total, active, inactive
 *
 * Colonnes alignées sur la table `categories` : nom, parent_id, status.
 * `parent` vaut null pour une catégorie racine ; la vue affiche alors « — ».
 *
 * Données statiques (étape 4) : aucun accès base de données.
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
        <span class="font-semibold text-stone-900">Référentiel de démonstration.</span>
        Les catégories réelles (table <code>categories</code>, colonnes
        <code>nom</code>, <code>parent_id</code>, <code>status</code>) seront chargées
        à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Catégories</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['total'] ?> entrée<?= ((int) $compteurs['total']) > 1 ? 's' : '' ?>
            — catégories et sous-catégories
        </p>
    </div>
</div>

<!-- Compteurs du référentiel -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="dash-count">Total <strong class="ml-1"><?= (int) $compteurs['total'] ?></strong></span>
    <span class="dash-badge dash-badge--actif"><?= (int) $compteurs['active'] ?> active(s)</span>
    <span class="dash-badge dash-badge--expire"><?= (int) $compteurs['inactive'] ?> inactive(s)</span>
</div>

<!-- Tableau du référentiel -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Catégories de la plateforme</caption>
            <thead>
                <tr>
                    <th scope="col">Catégorie</th>
                    <th scope="col">Rattachement</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Annonces</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $categorie): ?>
                    <tr>
                        <td class="font-medium text-stone-900"><?= $esc($categorie['nom'] ?? '') ?></td>
                        <td>
                            <?php if (($categorie['parent'] ?? null) === null): ?>
                                <span class="text-xs text-stone-500">Catégorie racine</span>
                            <?php else: ?>
                                <span class="text-xs text-stone-500">
                                    Sous-catégorie de <?= $esc($categorie['parent']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= dashBadgeStatut((string) ($categorie['statut'] ?? '')) ?></td>
                        <td><?= $esc(number_format((int) ($categorie['annonces'] ?? 0), 0, ',', ' ')) ?></td>
                        <td>
                            <div class="dash-actions">
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Renommage disponible à l'étape 7">
                                    <?= dashIcon('crayon') ?><span>Renommer</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Désactivation disponible à l'étape 7">
                                    <?= dashIcon('refuser') ?><span>Désactiver</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Rappel de sécurité -->
<p class="mt-4 text-xs text-stone-500">
    Une catégorie encore rattachée à des annonces ne devra pas pouvoir être supprimée :
    sa désactivation (<code>status = 'inactive'</code>) est la seule action prévue, afin de
    ne jamais rompre les liens <code>annonces.categorie_id</code>.
</p>
