<?php
/**
 * Vue : annonces en attente de validation (GET /moderateur/annonces).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::annonces() :
 *   $annonces : file d'attente (titre, membre, categorie, type, prix,
 *               suffixe, soumis, signalements)
 *   $recentes : dernières décisions (titre, membre, decision, date)
 *
 * Données statiques (étape 3) : aucun accès base de données.
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
        <span class="font-semibold text-stone-900">File de démonstration.</span>
        Les décisions (colonne <code>annonces.status</code> : <code>active</code>,
        <code>suspendue</code>, <code>expirée</code>) seront appliquées en base à l'étape 7.
    </p>
</div>

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

<!-- File d'attente -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Annonces en attente de validation</caption>
            <thead>
                <tr>
                    <th scope="col">Annonce</th>
                    <th scope="col">Membre</th>
                    <th scope="col">Prix</th>
                    <th scope="col">Signalements</th>
                    <th scope="col">Soumise</th>
                    <th scope="col">Décision</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($annonces as $annonce): ?>
                    <?php $nombreSignalements = (int) ($annonce['signalements'] ?? 0); ?>
                    <tr>
                        <td>
                            <span class="font-medium text-stone-900"><?= $esc($annonce['titre'] ?? '') ?></span>
                            <span class="block text-xs text-stone-500">
                                <?= $esc($annonce['categorie'] ?? '') ?> ·
                                <?= $esc($annonce['type'] ?? '') ?>
                            </span>
                        </td>
                        <td><?= $esc($annonce['membre'] ?? '') ?></td>
                        <td class="whitespace-nowrap">
                            <?= $esc(formatFcfa($annonce['prix'] ?? null)) ?><?= $esc($annonce['suffixe'] ?? '') ?>
                        </td>
                        <td>
                            <?php if ($nombreSignalements > 0): ?>
                                <span class="dash-badge dash-badge--rejete"><?= $nombreSignalements ?></span>
                            <?php else: ?>
                                <span class="dash-badge dash-badge--neutre">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap text-stone-500"><?= $esc($annonce['soumis'] ?? '') ?></td>
                        <td>
                            <div class="dash-actions">
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Décision disponible à l'étape 7">
                                    <?= dashIcon('valider') ?><span>Approuver</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Décision disponible à l'étape 7">
                                    <?= dashIcon('refuser') ?><span>Suspendre</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Décision disponible à l'étape 7">
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

<!-- Dernières décisions -->
<section class="dash-card mt-5">
    <div class="dash-card-head">
        <div>
            <h3 class="dash-card-title">Dernières décisions</h3>
            <p class="dash-card-subtitle">Annonces récemment traitées</p>
        </div>
    </div>

    <ul class="space-y-3">
        <?php foreach ($recentes as $recente): ?>
            <li class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-stone-900">
                        <?= $esc($recente['titre'] ?? '') ?>
                    </p>
                    <p class="text-xs text-stone-500">
                        <?= $esc($recente['membre'] ?? '') ?> · <?= $esc($recente['date'] ?? '') ?>
                    </p>
                </div>
                <?= dashBadgeStatut((string) ($recente['decision'] ?? '')) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
