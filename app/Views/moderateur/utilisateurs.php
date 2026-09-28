<?php
/**
 * Vue : consultation des comptes (GET /moderateur/utilisateurs).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::utilisateurs() :
 *   $utilisateurs : comptes (nom, email, role, statut, inscrit, annonces)
 *
 * PAGE EN LECTURE SEULE : le rôle modérateur consulte les comptes, il ne
 * modifie ni les rôles ni les statuts (gestion réservée à l'espace
 * administrateur, étape 4). Aucun bouton d'action n'est donc proposé.
 *
 * Le rôle technique est affiché tel quel ('member', 'moderateur', 'admin'),
 * sans créer de second référentiel de libellés.
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
        <span class="font-semibold text-stone-900">Consultation seule.</span>
        Les comptes affichés sont fictifs (table <code>users</code>). Un modérateur
        consulte les comptes mais ne modifie ni les rôles ni les statuts.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Utilisateurs</h2>
        <p class="dash-section-desc">
            <?= count($utilisateurs) ?> compte<?= count($utilisateurs) > 1 ? 's' : '' ?> consulté<?= count($utilisateurs) > 1 ? 's' : '' ?>
        </p>
    </div>

    <span class="dash-badge dash-badge--neutre">Lecture seule</span>
</div>

<!-- Tableau des comptes -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Comptes de la plateforme (lecture seule)</caption>
            <thead>
                <tr>
                    <th scope="col">Utilisateur</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Inscrit</th>
                    <th scope="col">Annonces</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $utilisateur): ?>
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
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    La modification des rôles et des statuts de compte relève de l'espace administrateur :
    le rôle modérateur dispose d'un accès en consultation uniquement.
</p>
