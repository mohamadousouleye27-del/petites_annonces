<?php
/**
 * Vue : file des signalements (GET /moderateur/signalements).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::signalements() :
 *   $signalements : liste (annonce, raison, signale_par, auteur, date, statut)
 *   $compteurs    : total, en_attente, traite, rejete
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
        Les signalements réels (table <code>signalements</code>) et leur traitement
        (<code>status</code>, <code>resolved_at</code>, <code>resolved_by</code>) seront
        branchés à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Signalements</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['en_attente'] ?> en attente de traitement
            sur <?= (int) $compteurs['total'] ?> signalement(s)
        </p>
    </div>
</div>

<!-- Compteurs par statut -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="dash-count">Total <strong class="ml-1"><?= (int) $compteurs['total'] ?></strong></span>
    <span class="dash-badge dash-badge--attente"><?= (int) $compteurs['en_attente'] ?> en attente</span>
    <span class="dash-badge dash-badge--actif"><?= (int) $compteurs['traite'] ?> traité(s)</span>
    <span class="dash-badge dash-badge--rejete"><?= (int) $compteurs['rejete'] ?> rejeté(s)</span>
</div>

<!-- Tableau des signalements -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Signalements déposés par les membres</caption>
            <thead>
                <tr>
                    <th scope="col">Annonce</th>
                    <th scope="col">Motif</th>
                    <th scope="col">Signalé par</th>
                    <th scope="col">Reçu</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signalements as $signalement): ?>
                    <?php $traitable = ($signalement['statut'] ?? '') === 'en_attente'; ?>
                    <tr>
                        <td>
                            <span class="font-medium text-stone-900"><?= $esc($signalement['annonce'] ?? '') ?></span>
                            <span class="block text-xs text-stone-500">
                                Propriétaire : <?= $esc($signalement['auteur'] ?? '') ?>
                            </span>
                        </td>
                        <td><?= $esc($signalement['raison'] ?? '') ?></td>
                        <td><?= $esc($signalement['signale_par'] ?? '') ?></td>
                        <td class="whitespace-nowrap text-stone-500"><?= $esc($signalement['date'] ?? '') ?></td>
                        <td><?= dashBadgeStatut((string) ($signalement['statut'] ?? '')) ?></td>
                        <td>
                            <div class="dash-actions">
                                <button
                                    type="button"
                                    class="dash-btn dash-btn--ghost dash-btn--sm"
                                    disabled
                                    title="<?= $traitable ? "Traitement disponible à l'étape 7" : 'Signalement déjà traité' ?>"
                                >
                                    <?= dashIcon('valider') ?><span>Traiter</span>
                                </button>
                                <button
                                    type="button"
                                    class="dash-btn dash-btn--ghost dash-btn--sm"
                                    disabled
                                    title="<?= $traitable ? "Rejet disponible à l'étape 7" : 'Signalement déjà traité' ?>"
                                >
                                    <?= dashIcon('refuser') ?><span>Rejeter</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    Périmètre du rôle modérateur : traiter ou rejeter les signalements et statuer sur les
    annonces en attente. La gestion des comptes et des rôles relève de l'espace administrateur.
</p>
