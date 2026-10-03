<?php
/**
 * Vue : référentiel des catégories (GET /admin/categories).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::categories()
 * (palier 7.3 — données réelles de MariaDB, LECTURE SEULE) :
 *   $categories : lignes du référentiel (id, nom, parent_id, parent_nom,
 *                 status, nb_annonces, nb_sous_categories)
 *   $compteurs  : total, active, inactive
 *
 * Colonnes alignées sur la table `categories` : nom, parent_id, status.
 * `parent_nom` est NULL pour une catégorie racine : la vue affiche alors
 * « Catégorie racine ». La hiérarchie est celle réellement stockée en base.
 *
 * AUCUNE écriture : ni ajout, ni renommage, ni suppression, ni déplacement,
 * ni changement de hiérarchie. Les boutons sont DÉSACTIVÉS.
 *
 * Aucune requête SQL ici. Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- Avertissement lecture seule -->
<div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
    <span class="mt-0.5 text-amber-500">
        <?= dashIcon('signalements') ?>
    </span>
    <p class="text-sm text-stone-700">
        <span class="font-semibold text-stone-900">Consultation seule.</span>
        L'arborescence affichée est celle réellement enregistrée dans la table
        <code>categories</code> (colonnes <code>nom</code>, <code>parent_id</code>,
        <code>status</code>). Aucun ajout, renommage, déplacement ni suppression
        n'est possible à ce stade.
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

<?php if ($categories === []): ?>
    <!-- État vide : aucune catégorie en base -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune catégorie disponible</p>
            <p>Le référentiel des catégories est vide.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau du référentiel -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Catégories de la plateforme (lecture seule)</caption>
                <thead>
                    <tr>
                        <th scope="col">Catégorie</th>
                        <th scope="col">Rattachement</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Annonces</th>
                        <th scope="col">Sous-catégories</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $categorie): ?>
                        <tr>
                            <td class="font-medium text-stone-900"><?= $esc($categorie['nom'] ?? '') ?></td>
                            <td>
                                <?php if (($categorie['parent_nom'] ?? null) === null): ?>
                                    <span class="text-xs text-stone-500">Catégorie racine</span>
                                <?php else: ?>
                                    <span class="text-xs text-stone-500">
                                        Sous-catégorie de <?= $esc($categorie['parent_nom']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= dashBadgeStatut((string) ($categorie['status'] ?? '')) ?></td>
                            <td><?= $esc(number_format((int) ($categorie['nb_annonces'] ?? 0), 0, ',', ' ')) ?></td>
                            <td><?= (int) ($categorie['nb_sous_categories'] ?? 0) ?></td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('crayon') ?><span>Renommer</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
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
<?php endif; ?>

<!-- Rappel de sécurité -->
<p class="mt-4 text-xs text-stone-500">
    Une catégorie encore rattachée à des annonces ne devra pas pouvoir être supprimée :
    sa désactivation (<code>status = 'inactive'</code>) est la seule action prévue, afin de
    ne jamais rompre les liens <code>annonces.categorie_id</code>.
</p>
