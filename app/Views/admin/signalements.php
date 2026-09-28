<?php
/**
 * Vue : vue globale des signalements (GET /admin/signalements).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::signalements() :
 *   $signalements : liste (annonce, raison, signale_par, auteur, date,
 *                   statut, traite_par, resolu_le)
 *   $compteurs    : total, en_attente, traite, rejete
 *
 * Différence avec l'espace modérateur (étape 3) : la vue est globale et
 * affiche la TRAÇABILITÉ (`resolved_by`, `resolved_at`), sans action de
 * traitement — celles-ci restent du ressort des modérateurs.
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
        <span class="font-semibold text-stone-900">Vue globale de démonstration.</span>
        Les signalements réels et leur traçabilité (<code>resolved_by</code>,
        <code>resolved_at</code>) seront chargés à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Signalements</h2>
        <p class="dash-section-desc">
            Vue globale — <?= (int) $compteurs['en_attente'] ?> en attente
            sur <?= (int) $compteurs['total'] ?> signalement(s)
        </p>
    </div>

    <span class="dash-badge dash-badge--neutre">Traçabilité complète</span>
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
            <caption class="sr-only">Signalements de la plateforme et traçabilité</caption>
            <thead>
                <tr>
                    <th scope="col">Annonce</th>
                    <th scope="col">Motif</th>
                    <th scope="col">Signalé par</th>
                    <th scope="col">Reçu</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Traité par</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signalements as $signalement): ?>
                    <?php $traite = ($signalement['traite_par'] ?? '') !== ''; ?>
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
                            <?php if ($traite): ?>
                                <span class="font-medium text-stone-900"><?= $esc($signalement['traite_par']) ?></span>
                                <span class="block text-xs text-stone-500">
                                    <?= $esc($signalement['resolu_le'] ?? '') ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-stone-400">Non traité</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    Le traitement des signalements (statut, <code>resolved_by</code>, <code>resolved_at</code>)
    relève des modérateurs ; l'administrateur dispose ici d'une vue de contrôle globale,
    sans action de décision.
</p>
