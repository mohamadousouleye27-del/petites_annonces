<?php
/**
 * Vue : tableau de bord de la plateforme (GET /admin).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php par Controller::viewWithLayout().
 *
 * Données fournies par App\Controllers\AdminController::dashboard()
 * (palier 7.3 — données réelles de MariaDB, lecture seule) :
 *   $stats        : cartes de statistiques (icone, valeur, label) issues
 *                   d'agrégats COUNT réels
 *   $repartition  : comptes par rôle technique (role, total)
 *   $referentiels : categories, categories_active, villes
 *   $journal      : dernières entrées d'audit (auteur_prenom, auteur_nom,
 *                   auteur_role, action, description, target_type,
 *                   ip_address, created_at)
 *
 * Données réelles issues de MariaDB via les modèles du palier 7.0.
 * Aucune requête SQL ici. Aucune valeur n'est affichée sans échappement.
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
        Toutes les valeurs ci-dessous proviennent de la base MariaDB
        (<code>users</code>, <code>annonces</code>, <code>signalements</code>,
        <code>categories</code>, <code>villes</code>, <code>audit_logs</code>).
        Aucune action administrative n'est proposée à ce stade.
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
                    <?php if ($journal === []): ?>
                        <tr>
                            <td colspan="4" class="py-6 text-center text-stone-500">
                                Aucune activité enregistrée.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($journal as $entree): ?>
                        <?php
                        $auteur = trim(
                            (string) ($entree['auteur_prenom'] ?? '')
                            . ' '
                            . (string) ($entree['auteur_nom'] ?? '')
                        );
                        ?>
                        <tr>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashTempsRelatif($entree['created_at'] ?? null)) ?>
                            </td>
                            <td>
                                <span class="font-medium text-stone-900">
                                    <?= $esc($auteur !== '' ? $auteur : '—') ?>
                                </span>
                                <span class="block text-xs text-stone-500">
                                    <code><?= $esc($entree['auteur_role'] ?? '—') ?></code>
                                </span>
                            </td>
                            <td><?= $esc($entree['action'] ?? '') ?></td>
                            <td>
                                <?= $esc($entree['description'] ?? '—') ?>
                                <span class="block text-xs text-stone-500">
                                    <code><?= $esc($entree['target_type'] ?? '—') ?></code>
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

        <!-- Référentiels de la plateforme -->
        <section class="dash-card">
            <div class="dash-card-head">
                <div>
                    <h3 class="dash-card-title">Référentiels</h3>
                    <p class="dash-card-subtitle">Catégories et villes</p>
                </div>
            </div>

            <ul class="space-y-3">
                <li class="flex items-center justify-between">
                    <span class="dash-badge dash-badge--neutre">Catégories</span>
                    <span class="font-poppins text-sm font-bold text-stone-900">
                        <?= (int) ($referentiels['categories'] ?? 0) ?>
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="dash-badge dash-badge--actif">dont actives</span>
                    <span class="font-poppins text-sm font-bold text-stone-900">
                        <?= (int) ($referentiels['categories_active'] ?? 0) ?>
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="dash-badge dash-badge--neutre">Villes</span>
                    <span class="font-poppins text-sm font-bold text-stone-900">
                        <?= (int) ($referentiels['villes'] ?? 0) ?>
                    </span>
                </li>
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

