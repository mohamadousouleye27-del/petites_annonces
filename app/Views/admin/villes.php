<?php
/**
 * Vue : référentiel des villes (GET /admin/villes).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::villes() :
 *   $villes    : lignes du référentiel (nom, type, parent, annonces)
 *   $compteurs : total (la table `villes` n'a pas de colonne `statut`)
 *
 * `parent` vaut null pour une région ; la vue affiche alors « Région ».
 * Le type technique est affiché tel quel ('region', 'departement',
 * 'commune'), sans créer de référentiel de libellés parallèle.
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
        Les villes réelles (table <code>villes</code>, colonnes <code>nom</code>,
        <code>type</code>, <code>parent_id</code>) seront chargées à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Villes</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['total'] ?> entrée<?= ((int) $compteurs['total']) > 1 ? 's' : '' ?>
            — régions, départements et communes
        </p>
    </div>
</div>

<!-- Tableau du référentiel -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Villes et découpages administratifs</caption>
            <thead>
                <tr>
                    <th scope="col">Ville</th>
                    <th scope="col">Type</th>
                    <th scope="col">Rattachement</th>
                    <th scope="col">Annonces</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($villes as $ville): ?>
                    <tr>
                        <td class="font-medium text-stone-900"><?= $esc($ville['nom'] ?? '') ?></td>
                        <td>
                            <span class="dash-badge dash-badge--neutre">
                                <code><?= $esc($ville['type'] ?? '') ?></code>
                            </span>
                        </td>
                        <td>
                            <?php if (($ville['parent'] ?? null) === null): ?>
                                <span class="text-xs text-stone-500">Région</span>
                            <?php else: ?>
                                <span class="text-xs text-stone-500">
                                    Rattachée à <?= $esc($ville['parent']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= $esc(number_format((int) ($ville['annonces'] ?? 0), 0, ',', ' ')) ?></td>
                        <td>
                            <div class="dash-actions">
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Renommage disponible à l'étape 7">
                                    <?= dashIcon('crayon') ?><span>Renommer</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Suppression disponible à l'étape 7">
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

<!-- Rappel de sécurité -->
<p class="mt-4 text-xs text-stone-500">
    Comme pour les catégories, une ville rattachée à des annonces ou à des entités filles
    ne devra pas être supprimable : la cohérence des liens <code>villes.parent_id</code> et
    <code>annonces.ville_id</code> sera contrôlée côté serveur à l'étape 7.
</p>
