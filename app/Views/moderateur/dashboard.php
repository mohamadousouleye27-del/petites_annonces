<?php
/**
 * Vue : tableau de bord de la modération (GET /moderateur).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php par Controller::viewWithLayout().
 *
 * Données fournies par App\Controllers\ModerateurController::dashboard() :
 *   $stats        : cartes de statistiques (icone, valeur, label)
 *   $signalements : signalements récents (annonce, raison, signale_par, date, statut)
 *   $annonces     : annonces en attente (titre, membre, categorie, type, prix,
 *                   suffixe, soumis, signalements)
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
        <span class="font-semibold text-stone-900">Interfaces en cours de construction.</span>
        Les files de modération affichées sont fictives&nbsp;: le branchement sur les tables
        <code>signalements</code> et <code>annonces</code> est prévu à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">File de modération</h2>
        <p class="dash-section-desc">Priorités du jour</p>
    </div>

    <a class="dash-btn dash-btn--primary" href="<?= base_path('moderateur/signalements') ?>">
        <?= dashIcon('signalements') ?>
        <span>Traiter les signalements</span>
    </a>
</div>

<!-- Statistiques -->
<div class="dash-grid dash-grid--stats">
    <?php foreach ($stats as $stat): ?>
        <article class="dash-stat dash-animate-in">
            <div class="dash-stat-icon">
                <?= dashIcon((string) ($stat['icone'] ?? 'neutre')) ?>
            </div>
            <div>
                <p class="dash-stat-value"><?= $esc($stat['valeur'] ?? '') ?></p>
                <p class="dash-stat-label"><?= $esc($stat['label'] ?? '') ?></p>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<!-- Fils de modération -->
<div class="mt-5 grid gap-4 lg:grid-cols-2">

    <!-- Signalements récents -->
    <section class="dash-card dash-card--flush">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Signalements récents</h3>
                <p class="dash-card-subtitle">Les 4 derniers signalements reçus</p>
            </div>
            <a class="dash-btn dash-btn--ghost" href="<?= base_path('moderateur/signalements') ?>">
                <span>Tout voir</span>
                <?= dashIcon('fleche') ?>
            </a>
        </div>

        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Signalements récents</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Motif</th>
                        <th scope="col">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signalements as $signalement): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($signalement['annonce'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    Signalé par <?= $esc($signalement['signale_par'] ?? '') ?>
                                    · <?= $esc($signalement['date'] ?? '') ?>
                                </span>
                            </td>
                            <td><?= $esc($signalement['raison'] ?? '') ?></td>
                            <td><?= dashBadgeStatut((string) ($signalement['statut'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Annonces en attente de validation -->
    <section class="dash-card">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Annonces en attente</h3>
                <p class="dash-card-subtitle">À valider avant publication</p>
            </div>
            <a class="dash-btn dash-btn--ghost" href="<?= base_path('moderateur/annonces') ?>">
                <span>Tout voir</span>
                <?= dashIcon('fleche') ?>
            </a>
        </div>

        <ul class="space-y-3">
            <?php foreach ($annonces as $annonce): ?>
                <li class="flex items-start justify-between gap-3 border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-stone-900">
                            <?= $esc($annonce['titre'] ?? '') ?>
                        </p>
                        <p class="text-xs text-stone-500">
                            <?= $esc($annonce['membre'] ?? '') ?> ·
                            <?= $esc($annonce['categorie'] ?? '') ?> ·
                            <?= $esc($annonce['soumis'] ?? '') ?>
                        </p>
                        <?php if ((int) ($annonce['signalements'] ?? 0) > 0): ?>
                            <p class="mt-1">
                                <span class="dash-badge dash-badge--rejete">
                                    <?= (int) $annonce['signalements'] ?> signalement(s)
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <p class="shrink-0 text-sm font-semibold text-stone-900">
                        <?= $esc(formatFcfa($annonce['prix'] ?? null)) ?><?= $esc($annonce['suffixe'] ?? '') ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

