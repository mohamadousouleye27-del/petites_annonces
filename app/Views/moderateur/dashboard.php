<?php
/**
 * Vue : tableau de bord de la modération (GET /moderateur).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php par Controller::viewWithLayout().
 *
 * Données fournies par App\Controllers\ModerateurController::dashboard()
 * (palier 7.2 — lecture seule, données réelles de la base) :
 *   $stats        : cartes de statistiques (icone, valeur, label)
 *   $signalements : signalements récents — colonnes du modèle Signalement
 *                   (annonce_titre, signaleur_prenom/nom, raison, status,
 *                   created_at)
 *   $annonces     : annonces en attente — colonnes du modèle Annonce (titre,
 *                   categorie_nom, type_annonce, membre_prenom/nom, prix,
 *                   nb_signalements, created_at)
 *
 * Aucune requête SQL ici : la vue affiche les données du contrôleur, mises en
 * forme par les helpers d'affichage (dashBadgeStatut, dashTempsRelatif,
 * dashPrixAffiche). Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

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

        <?php if ($signalements === []): ?>
            <!-- État vide : aucun signalement en base -->
            <div class="dash-empty">
                <p class="dash-empty-title">Aucun signalement</p>
                <p>Les signalements déposés par les membres apparaîtront ici.</p>
            </div>
        <?php else: ?>
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
                            <?php
                            $signaleur = trim(
                                (string) ($signalement['signaleur_prenom'] ?? '')
                                . ' '
                                . (string) ($signalement['signaleur_nom'] ?? '')
                            );
                            ?>
                            <tr>
                                <td>
                                    <span class="font-medium text-stone-900"><?= $esc($signalement['annonce_titre'] ?? '') ?></span>
                                    <span class="block text-xs text-stone-500">
                                        Signalé par <?= $esc($signaleur !== '' ? $signaleur : '—') ?>
                                        · <?= $esc(dashTempsRelatif($signalement['created_at'] ?? null)) ?>
                                    </span>
                                </td>
                                <td><?= $esc($signalement['raison'] ?? '') ?></td>
                                <td><?= dashBadgeStatut((string) ($signalement['status'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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

        <?php if ($annonces === []): ?>
            <!-- État vide : aucune annonce en attente -->
            <div class="dash-empty">
                <p class="dash-empty-title">Aucune annonce en attente</p>
                <p>Les annonces soumises à validation apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($annonces as $annonce): ?>
                    <?php
                    $membre = trim(
                        (string) ($annonce['membre_prenom'] ?? '')
                        . ' '
                        . (string) ($annonce['membre_nom'] ?? '')
                    );
                    ?>
                    <li class="flex items-start justify-between gap-3 border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-stone-900">
                                <?= $esc($annonce['titre'] ?? '') ?>
                            </p>
                            <p class="text-xs text-stone-500">
                                <?= $esc($membre) ?> ·
                                <?= $esc($annonce['categorie_nom'] ?? '') ?> ·
                                <?= $esc(dashTempsRelatif($annonce['created_at'] ?? null)) ?>
                            </p>
                            <?php if ((int) ($annonce['nb_signalements'] ?? 0) > 0): ?>
                                <p class="mt-1">
                                    <span class="dash-badge dash-badge--rejete">
                                        <?= (int) $annonce['nb_signalements'] ?> signalement(s)
                                    </span>
                                </p>
                            <?php endif; ?>
                        </div>

                        <p class="shrink-0 text-sm font-semibold text-stone-900">
                            <?= $esc(dashPrixAffiche($annonce['prix'] ?? null, (string) ($annonce['type_annonce'] ?? ''))) ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
