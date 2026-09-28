<?php
/**
 * Vue : administration des comptes (GET /admin/utilisateurs).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::utilisateurs() :
 *   $utilisateurs : comptes (nom, email, role, statut, inscrit, annonces)
 *   $repartition  : comptes par rôle technique (role, total)
 *
 * SEUL espace habilité à administrer les rôles et les statuts de compte
 * (le rôle modérateur n'a qu'un accès en consultation, étape 3).
 * Les actions restent INACTIVES tant que la validation serveur n'existe pas :
 * leurs boutons sont désactivés, aucune écriture ne peut être déclenchée.
 *
 * Le rôle technique est affiché tel quel ('member', 'moderateur', 'admin'),
 * sans créer de second référentiel de libellés de rôle.
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
        <span class="font-semibold text-stone-900">Comptes de démonstration.</span>
        Les écritures sur <code>users.role</code> et <code>users.status</code> seront
        implémentées à l'étape 7, avec validation serveur, protection CSRF et
        journalisation dans <code>audit_logs</code>. L'auto-modification du compte
        connecté y sera refusée.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Utilisateurs</h2>
        <p class="dash-section-desc">
            <?= count($utilisateurs) ?> compte<?= count($utilisateurs) > 1 ? 's' : '' ?>
            — administration des rôles et des statuts
        </p>
    </div>

    <span class="dash-badge dash-badge--actif">Administration des comptes</span>
</div>

<!-- Répartition par rôle -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <?php foreach ($repartition as $ligne): ?>
        <span class="dash-count">
            <code><?= $esc($ligne['role'] ?? '') ?></code>
            <strong class="ml-1"><?= (int) ($ligne['total'] ?? 0) ?></strong>
        </span>
    <?php endforeach; ?>
</div>

<!-- Tableau des comptes -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Comptes de la plateforme et administration</caption>
            <thead>
                <tr>
                    <th scope="col">Utilisateur</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Inscrit</th>
                    <th scope="col">Annonces</th>
                    <th scope="col">Administration</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $utilisateur): ?>
                    <?php $actif = ($utilisateur['statut'] ?? '') === 'active'; ?>
                    <tr>
                        <td>
                            <span class="font-medium text-stone-900"><?= $esc($utilisateur['nom'] ?? '') ?></span>
                            <span class="block text-xs text-stone-500"><?= $esc($utilisateur['email'] ?? '') ?></span>
                        </td>
                        <td>
                            <span class="dash-badge dash-badge--neutre">
                                <code><?= $esc($utilisateur['role'] ?? '') ?></code>
                            </span>
                        </td>
                        <td><?= dashBadgeStatut((string) ($utilisateur['statut'] ?? '')) ?></td>
                        <td class="whitespace-nowrap text-stone-500"><?= $esc($utilisateur['inscrit'] ?? '') ?></td>
                        <td><?= (int) ($utilisateur['annonces'] ?? 0) ?></td>
                        <td>
                            <div class="dash-actions">
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Modification du rôle disponible à l'étape 7">
                                    <?= dashIcon('parametres') ?><span>Changer le rôle</span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Modification du statut disponible à l'étape 7">
                                    <?= dashIcon($actif ? 'refuser' : 'valider') ?>
                                    <span><?= $actif ? 'Suspendre' : 'Réactiver' ?></span>
                                </button>
                                <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Bannissement disponible à l'étape 7">
                                    <?= dashIcon('poubelle') ?><span>Bannir</span>
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
    Chaque modification de rôle ou de statut devra être validée côté serveur, protégée par un
    jeton CSRF et journalisée dans <code>audit_logs</code>. Un administrateur ne pourra pas
    modifier son propre compte (protection contre la perte d'accès).
</p>

