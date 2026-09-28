<?php
/**
 * Vue : tableau de bord du membre (GET /membre).
 *
 * Vue de CONTENU uniquement : ce fichier ne produit ni <head>, ni sidebar,
 * ni footer. Il est injecté dans la zone $content du layout commun
 * app/Views/layouts/connected.php par Controller::viewWithLayout().
 *
 * Données fournies par App\Controllers\MembreController::dashboard() :
 *   $stats    : cartes de statistiques (icone, valeur, label)
 *   $annonces : dernières annonces (titre, categorie, type, prix, suffixe, statut, vues, date)
 *   $messages : messages récents (expediteur, sujet, extrait, date, non_lu)
 *
 * Données statiques (étape 2) : aucun accès base de données.
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
        Les données affichées sont fictives&nbsp;: la connexion à la base de données
        est prévue à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Vue d'ensemble</h2>
        <p class="dash-section-desc">Votre activité des 30 derniers jours</p>
    </div>

    <button
        type="button"
        class="dash-btn dash-btn--primary"
        disabled
        title="La publication sera activée à l'étape 7 (données dynamiques)"
    >
        <?= dashIcon('plus') ?>
        <span>Publier une annonce</span>
    </button>
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

<!-- Dernières annonces et messages récents -->
<div class="mt-5 grid gap-4 lg:grid-cols-3">

    <!-- Dernières annonces -->
    <section class="dash-card dash-card--flush lg:col-span-2">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Mes dernières annonces</h3>
                <p class="dash-card-subtitle">Les 4 annonces publiées le plus récemment</p>
            </div>
            <a class="dash-btn dash-btn--ghost" href="<?= base_path('membre/annonces') ?>">
                <span>Tout voir</span>
                <?= dashIcon('fleche') ?>
            </a>
        </div>

        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Dernières annonces du membre</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Prix</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Vues</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($annonces as $annonce): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($annonce['titre'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    <?= $esc($annonce['categorie'] ?? '') ?> ·
                                    <?= $esc($annonce['type'] ?? '') ?> ·
                                    <?= $esc($annonce['date'] ?? '') ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <?= $esc(formatFcfa($annonce['prix'] ?? null)) ?><?= $esc($annonce['suffixe'] ?? '') ?>
                            </td>
                            <td><?= dashBadgeStatut((string) ($annonce['statut'] ?? '')) ?></td>
                            <td><?= $esc(number_format((int) ($annonce['vues'] ?? 0), 0, ',', ' ')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Messages récents -->
    <section class="dash-card">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Messages récents</h3>
                <p class="dash-card-subtitle">Vos derniers échanges</p>
            </div>
        </div>

        <ul class="space-y-3">
            <?php foreach ($messages as $message): ?>
                <li class="flex items-start gap-3">
                    <span
                        class="mt-1 inline-block h-2 w-2 shrink-0 rounded-full <?= ($message['non_lu'] ?? false) ? 'bg-amber-400' : 'bg-stone-300' ?>"
                        aria-hidden="true"
                    ></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-stone-900">
                            <?= $esc($message['expediteur'] ?? '') ?>
                        </p>
                        <p class="truncate text-xs text-stone-500">
                            <?= $esc($message['sujet'] ?? '') ?>
                        </p>
                        <p class="mt-0.5 text-xs text-stone-400">
                            <?= $esc($message['date'] ?? '') ?>
                            <?php if (($message['non_lu'] ?? false) === true): ?>
                                <span class="ml-1 font-semibold text-amber-600">nouveau</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <a class="dash-btn dash-btn--ghost mt-4 w-full" href="<?= base_path('membre/messages') ?>">
            <span>Ouvrir la messagerie</span>
            <?= dashIcon('fleche') ?>
        </a>
    </section>
</div>

