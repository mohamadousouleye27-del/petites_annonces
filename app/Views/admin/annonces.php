<?php
/**
 * Vue : toutes les annonces (GET /admin/annonces).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::annonces()
 * (palier 7.3 — données réelles de MariaDB, LECTURE SEULE) :
 *   $annonces  : annonces, colonnes du modèle Annonce (titre, membre_prenom,
 *                membre_nom, categorie_nom, ville_nom, quartier, prix,
 *                type_annonce, status, nb_vues, created_at, nb_favoris,
 *                nb_signalements)
 *   $compteurs : total, active, en_attente, expirée, suspendue
 *
 * Vue globale, tous statuts confondus (le modérateur ne voit que la file
 * « en_attente » dans son propre espace). AUCUNE création, modification,
 * suppression, validation, suspension ni changement de statut : les boutons
 * d'action sont DÉSACTIVÉS et ne déclenchent aucune écriture.
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
        Les annonces et leurs compteurs proviennent des tables
        <code>annonces</code>, <code>categories</code> et <code>villes</code>.
        Aucun changement de statut (validation, suspension, expiration) n'est
        possible à ce stade.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Annonces</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['total'] ?> annonce<?= ((int) $compteurs['total']) > 1 ? 's' : '' ?>
            sur la plateforme
        </p>
    </div>
</div>

<!-- Compteurs par statut -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="dash-count">Total <strong class="ml-1"><?= (int) $compteurs['total'] ?></strong></span>
    <span class="dash-badge dash-badge--actif"><?= (int) $compteurs['active'] ?> active(s)</span>
    <span class="dash-badge dash-badge--attente"><?= (int) $compteurs['en_attente'] ?> en attente</span>
    <span class="dash-badge dash-badge--expire"><?= (int) $compteurs['expirée'] ?> expirée(s)</span>
    <span class="dash-badge dash-badge--suspendu"><?= (int) $compteurs['suspendue'] ?> suspendue(s)</span>
</div>

<?php if ($annonces === []): ?>
    <!-- État vide : aucune annonce en base -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune annonce trouvée</p>
            <p>Aucune annonce n'est disponible en base.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau des annonces -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Toutes les annonces de la plateforme</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Membre</th>
                        <th scope="col">Type</th>
                        <th scope="col">Prix</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Vues</th>
                        <th scope="col">Publiée</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($annonces as $annonce): ?>
                        <?php
                        $membre = trim(
                            (string) ($annonce['membre_prenom'] ?? '')
                            . ' '
                            . (string) ($annonce['membre_nom'] ?? '')
                        );
                        $type = (string) ($annonce['type_annonce'] ?? '');
                        ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($annonce['titre'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    <?= $esc($annonce['categorie_nom'] ?? '—') ?> ·
                                    <?= $esc($annonce['ville_nom'] ?? '—') ?>
                                </span>
                            </td>
                            <td><?= $esc($membre !== '' ? $membre : '—') ?></td>
                            <td>
                                <span class="dash-badge dash-badge--neutre">
                                    <?= $esc($type !== '' ? dashTypeAnnonce($type) : '—') ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <?= $esc(dashPrixAffiche($annonce['prix'] ?? null, $type)) ?>
                            </td>
                            <td><?= dashBadgeStatut((string) ($annonce['status'] ?? '')) ?></td>
                            <td><?= $esc(number_format((int) ($annonce['nb_vues'] ?? 0), 0, ',', ' ')) ?></td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashTempsRelatif($annonce['created_at'] ?? null)) ?>
                            </td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('oeil') ?><span>Voir</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('crayon') ?><span>Modifier</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('refuser') ?><span>Suspendre</span>
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

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    La file d'attente du modérateur ne présente que les annonces <code>en_attente</code> ;
    l'administrateur dispose ici de la vue globale, tous statuts confondus.
</p>
