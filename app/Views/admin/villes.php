<?php
/**
 * Vue : référentiel des villes (GET /admin/villes).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::villes()
 * (palier 7.3 — données réelles de MariaDB, LECTURE SEULE) :
 *   $villes    : lignes du référentiel (id, nom, type, parent_id, parent_nom,
 *                nb_annonces, nb_sous_villes)
 *   $compteurs : total (la table `villes` n'a pas de colonne `status`)
 *
 * `parent_nom` vaut null pour une entrée sans rattachement ; la vue affiche
 * alors « Sans rattachement ». Le type technique est affiché tel quel
 * ('region', 'departement'...), sans créer de référentiel de libellés
 * parallèle : la colonne `type` est un varchar libre en base.
 *
 * AUCUNE écriture : ni ajout, ni renommage, ni suppression, ni changement de
 * hiérarchie. Les boutons sont DÉSACTIVÉS.
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
        Les villes affichées proviennent de la table <code>villes</code> (colonnes
        <code>nom</code>, <code>type</code>, <code>parent_id</code>). Cette table ne
        comporte pas de colonne <code>status</code>&nbsp;: aucun statut n'est donc
        affiché. Aucun ajout ni suppression n'est possible à ce stade.
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

<?php if ($villes === []): ?>
    <!-- État vide : aucune ville en base -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune ville disponible</p>
            <p>Le référentiel des villes est vide.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau du référentiel -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Villes et découpages administratifs (lecture seule)</caption>
                <thead>
                    <tr>
                        <th scope="col">Ville</th>
                        <th scope="col">Type</th>
                        <th scope="col">Rattachement</th>
                        <th scope="col">Annonces</th>
                        <th scope="col">Sous-villes</th>
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
                                <?php if (($ville['parent_nom'] ?? null) === null): ?>
                                    <span class="text-xs text-stone-500">Sans rattachement</span>
                                <?php else: ?>
                                    <span class="text-xs text-stone-500">
                                        Rattachée à <?= $esc($ville['parent_nom']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= $esc(number_format((int) ($ville['nb_annonces'] ?? 0), 0, ',', ' ')) ?></td>
                            <td><?= (int) ($ville['nb_sous_villes'] ?? 0) ?></td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('crayon') ?><span>Renommer</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
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

<!-- Rappel de sécurité -->
<p class="mt-4 text-xs text-stone-500">
    Comme pour les catégories, une ville rattachée à des annonces ou à des entités filles
    ne devra pas être supprimable : la cohérence des liens <code>villes.parent_id</code> et
    <code>annonces.ville_id</code> sera contrôlée côté serveur à l'étape 7.
</p>
