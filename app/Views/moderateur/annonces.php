<?php
/**
 * Vue : annonces en attente de décision (GET /moderateur/annonces).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::annonces()
 * (palier 7.2 — lecture seule, données réelles de la base) :
 *   $annonces : file d'attente, colonnes du modèle Annonce (titre,
 *               categorie_nom, type_annonce, membre_prenom/nom, ville_nom,
 *               prix, nb_signalements, created_at, status)
 *   $recentes : dernières décisions lues dans audit_logs (action, description,
 *               auteur_prenom/nom, created_at)
 *
 * Les boutons de décision sont volontairement DÉSACTIVÉS : ce palier est
 * strictement en lecture et n'effectue AUCUNE écriture (aucun changement de
 * statut). Aucune requête SQL ici. Aucune valeur n'est affichée sans
 * échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Annonces à modérer</h2>
        <p class="dash-section-desc">
            <?= count($annonces) ?> annonce<?= count($annonces) > 1 ? 's' : '' ?>
            en attente de décision
        </p>
    </div>
</div>

<?php if ($annonces === []): ?>
    <!-- État vide : aucune annonce en attente -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune annonce à modérer</p>
            <p>Les annonces soumises à validation apparaîtront ici.</p>
        </div>
    </section>
<?php else: ?>
    <!-- File d'attente -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Annonces en attente de validation</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Membre</th>
                        <th scope="col">Ville</th>
                        <th scope="col">Prix</th>
                        <th scope="col">Signalements</th>
                        <th scope="col">Soumise</th>
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
                        $nombreSignalements = (int) ($annonce['nb_signalements'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($annonce['titre'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    <?= $esc($annonce['categorie_nom'] ?? '') ?> ·
                                    <?= $esc(dashTypeAnnonce((string) ($annonce['type_annonce'] ?? ''))) ?>
                                </span>
                            </td>
                            <td><?= $esc($membre) ?></td>
                            <td class="text-stone-500"><?= $esc($annonce['ville_nom'] ?? '') ?></td>
                            <td class="whitespace-nowrap">
                                <?= $esc(dashPrixAffiche($annonce['prix'] ?? null, (string) ($annonce['type_annonce'] ?? ''))) ?>
                            </td>
                            <td>
                                <?php if ($nombreSignalements > 0): ?>
                                    <span class="dash-badge dash-badge--rejete"><?= $nombreSignalements ?></span>
                                <?php else: ?>
                                    <span class="dash-badge dash-badge--neutre">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashTempsRelatif($annonce['created_at'] ?? null)) ?>
                            </td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                                        <?= dashIcon('valider') ?><span>Approuver</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                                        <?= dashIcon('refuser') ?><span>Suspendre</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Action disponible dans une prochaine étape">
                                        <?= dashIcon('poubelle') ?><span>Rejeter</span>
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

<!-- Dernières décisions -->
<section class="dash-card mt-5">
    <div class="dash-card-head">
        <div>
            <h3 class="dash-card-title">Dernières décisions</h3>
            <p class="dash-card-subtitle">Annonces récemment traitées (journal d'audit)</p>
        </div>
    </div>

    <?php if ($recentes === []): ?>
        <!-- État vide : aucune décision enregistrée dans le journal d'audit -->
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune décision enregistrée</p>
            <p>Les décisions de modération apparaîtront ici.</p>
        </div>
    <?php else: ?>
        <ul class="space-y-3">
            <?php foreach ($recentes as $recente): ?>
                <?php
                $auteur = trim(
                    (string) ($recente['auteur_prenom'] ?? '')
                    . ' '
                    . (string) ($recente['auteur_nom'] ?? '')
                );
                ?>
                <li class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-stone-900">
                            <?= $esc($recente['action'] ?? '') ?>
                        </p>
                        <p class="text-xs text-stone-500">
                            <?= $esc($recente['description'] ?? '') ?>
                        </p>
                        <p class="text-xs text-stone-400">
                            <?= $esc($auteur !== '' ? $auteur : 'Système') ?>
                            · <?= $esc(dashTempsRelatif($recente['created_at'] ?? null)) ?>
                        </p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
