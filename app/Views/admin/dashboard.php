<?php
/**
 * Vue : tableau de bord de la plateforme (GET /admin).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php par Controller::viewWithLayout().
 *
 * Données fournies par App\Controllers\AdminController::dashboard() :
 *   $stats       : cartes de statistiques (icone, valeur, label)
 *   $repartition : comptes par rôle technique (role, total)
 *   $journal     : 5 dernières entrées d'audit (utilisateur, action, cible, type, ip, date)
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
        <span class="font-semibold text-stone-900">Interfaces en cours de construction.</span>
        Les chiffres affichés sont fictifs&nbsp;: les requêtes d'agrégation sur
        <code>users</code>, <code>annonces</code>, <code>signalements</code> et
        <code>audit_logs</code> sont prévues à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Vue d'ensemble</h2>
        <p class="dash-section-desc">État de la plateforme et activité récente</p>
    </div>

    <a class="dash-btn dash-btn--primary" href="<?= base_path('admin/utilisateurs') ?>">
        <?= dashIcon('utilisateurs') ?>
        <span>Gérer les comptes</span>
    </a>
</div>

<!-- Statistiques globales -->
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

<!-- Audit récent, répartition des comptes et accès rapides -->
<div class="mt-5 grid gap-4 lg:grid-cols-3">

    <!-- Dernières actions d'audit -->
    <section class="dash-card dash-card--flush lg:col-span-2">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Dernières actions d'audit</h3>
                <p class="dash-card-subtitle">Les 5 entrées les plus récentes</p>
            </div>
            <a class="dash-btn dash-btn--ghost" href="<?= base_path('admin/journal') ?>">
                <span>Journal complet</span>
                <?= dashIcon('fleche') ?>
            </a>
        </div>

        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Dernières actions d'audit</caption>
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Utilisateur</th>
                        <th scope="col">Action</th>
                        <th scope="col">Cible</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($journal as $entree): ?>
                        <tr>
                            <td class="whitespace-nowrap text-stone-500"><?= $esc($entree['date'] ?? '') ?></td>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($entree['utilisateur'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    <code><?= $esc($entree['role'] ?? '') ?></code>
                                </span>
                            </td>
                            <td><?= $esc($entree['action'] ?? '') ?></td>
                            <td>
                                <?= $esc($entree['cible'] ?? '') ?>
                                <span class="block text-xs text-stone-500">
                                    <code><?= $esc($entree['type'] ?? '') ?></code>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="space-y-4">

        <!-- Comptes par rôle -->
        <section class="dash-card">
            <div class="dash-card-head">
                <div>
                    <h3 class="dash-card-title">Comptes par rôle</h3>
                    <p class="dash-card-subtitle">Rôles techniques de la base</p>
                </div>
            </div>

            <ul class="space-y-3">
                <?php foreach ($repartition as $ligne): ?>
                    <li class="flex items-center justify-between">
                        <span class="dash-badge dash-badge--neutre">
                            <code><?= $esc($ligne['role'] ?? '') ?></code>
                        </span>
                        <span class="font-poppins text-sm font-bold text-stone-900">
                            <?= (int) ($ligne['total'] ?? 0) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Accès rapides -->
        <section class="dash-card">
            <div class="dash-card-head">
                <div>
                    <h3 class="dash-card-title">Accès rapides</h3>
                    <p class="dash-card-subtitle">Référentiels et modération</p>
                </div>
            </div>

            <nav class="space-y-1">
                <a class="dash-user-link" href="<?= base_path('admin/categories') ?>">
                    <?= dashIcon('categories') ?><span>Catégories</span>
                </a>
                <a class="dash-user-link" href="<?= base_path('admin/villes') ?>">
                    <?= dashIcon('villes') ?><span>Villes</span>
                </a>
                <a class="dash-user-link" href="<?= base_path('admin/signalements') ?>">
                    <?= dashIcon('signalements') ?><span>Signalements</span>
                </a>
                <a class="dash-user-link" href="<?= base_path('admin/annonces') ?>">
                    <?= dashIcon('annonces') ?><span>Annonces</span>
                </a>
            </nav>
        </section>
    </div>
</div>

