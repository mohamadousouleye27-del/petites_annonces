<?php
/**
 * Vue : tableau de bord du membre (GET /membre).
 *
 * Vue de CONTENU uniquement : ce fichier ne produit ni <head>, ni sidebar,
 * ni footer. Il est injecté dans la zone $content du layout commun
 * app/Views/layouts/connected.php par Controller::viewWithLayout().
 *
 * Données fournies par App\Controllers\MembreController::dashboard()
 * (palier 7.1 — lecture seule, données réelles de la base) :
 *   $stats         : cartes de statistiques (icone, valeur, label)
 *   $annonces      : dernières annonces du membre (colonnes du modèle Annonce :
 *                    titre, categorie_nom, type_annonce, prix, status, nb_vues, created_at)
 *   $conversations : conversations récentes (colonnes du modèle Message :
 *                    interlocuteur_prenom/nom, annonce_titre, dernier_message,
 *                    nb_messages, non_lus)
 *
 * Aucune requête SQL ici : la vue ne fait qu'afficher les données fournies
 * par le contrôleur, mises en forme par les helpers d'affichage
 * (dashTypeAnnonce, dashPrixAffiche, dashBadgeStatut, dashTempsRelatif).
 * Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

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
        title="La publication sera disponible dans une prochaine étape"
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

        <?php if ($annonces === []): ?>
            <!-- État vide : le membre n'a publié aucune annonce -->
            <div class="dash-empty">
                <p class="dash-empty-title">Aucune annonce</p>
                <p>Vos annonces publiées apparaîtront ici.</p>
            </div>
        <?php else: ?>
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
                                        <?= $esc($annonce['categorie_nom'] ?? '') ?> ·
                                        <?= $esc(dashTypeAnnonce((string) ($annonce['type_annonce'] ?? ''))) ?> ·
                                        <?= $esc(dashTempsRelatif($annonce['created_at'] ?? null)) ?>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap">
                                    <?= $esc(dashPrixAffiche($annonce['prix'] ?? null, (string) ($annonce['type_annonce'] ?? ''))) ?>
                                </td>
                                <td><?= dashBadgeStatut((string) ($annonce['status'] ?? '')) ?></td>
                                <td><?= $esc(number_format((int) ($annonce['nb_vues'] ?? 0), 0, ',', ' ')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Messages récents -->
    <section class="dash-card">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Messages récents</h3>
                <p class="dash-card-subtitle">Vos derniers échanges</p>
            </div>
        </div>

        <?php if ($conversations === []): ?>
            <!-- État vide : aucun échange -->
            <div class="dash-empty">
                <p class="dash-empty-title">Aucun message</p>
                <p>Vos échanges avec les autres membres apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($conversations as $conversation): ?>
                    <?php
                    $nbNonLus = (int) ($conversation['non_lus'] ?? 0);
                    $nbMessages = (int) ($conversation['nb_messages'] ?? 0);
                    $nomInterlocuteur = trim(
                        (string) ($conversation['interlocuteur_prenom'] ?? '')
                        . ' '
                        . (string) ($conversation['interlocuteur_nom'] ?? '')
                    );
                    ?>
                    <li class="flex items-start gap-3">
                        <span
                            class="mt-1 inline-block h-2 w-2 shrink-0 rounded-full <?= $nbNonLus > 0 ? 'bg-amber-400' : 'bg-stone-300' ?>"
                            aria-hidden="true"
                        ></span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-stone-900">
                                <?= $esc($nomInterlocuteur) ?>
                            </p>
                            <p class="truncate text-xs text-stone-500">
                                <?= $esc($conversation['annonce_titre'] ?? '') ?>
                            </p>
                            <p class="mt-0.5 text-xs text-stone-400">
                                <?= $esc(dashTempsRelatif($conversation['dernier_message'] ?? null)) ?>
                                ·
                                <?= $nbMessages ?> message<?= $nbMessages > 1 ? 's' : '' ?>
                                <?php if ($nbNonLus > 0): ?>
                                    <span class="ml-1 font-semibold text-amber-600">
                                        <?= $nbNonLus ?> non lu<?= $nbNonLus > 1 ? 's' : '' ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a class="dash-btn dash-btn--ghost mt-4 w-full" href="<?= base_path('membre/messages') ?>">
            <span>Ouvrir la messagerie</span>
            <?= dashIcon('fleche') ?>
        </a>
    </section>
</div>

