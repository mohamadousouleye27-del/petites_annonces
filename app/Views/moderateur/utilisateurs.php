<?php
/**
 * Vue : consultation des comptes (GET /moderateur/utilisateurs).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::utilisateurs()
 * (palier 7.2 — lecture seule, données réelles de la base) :
 *   $utilisateurs : comptes, colonnes du modèle User (prenom, nom, email,
 *                   role, ville_nom, status, created_at, nb_annonces)
 *
 * PAGE EN LECTURE SEULE : le modérateur consulte les comptes mais ne modifie
 * ni les rôles ni les statuts (gestion réservée à l'espace administrateur).
 * Aucun bouton d'action n'est proposé. Le mot de passe et les jetons ne sont
 * jamais affichés (le modèle ne les sélectionne pas).
 *
 * Aucune requête SQL ici. Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

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

<?php if ($utilisateurs === []): ?>
    <!-- État vide : aucun compte à afficher -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucun utilisateur</p>
            <p>Aucun compte n'est disponible en base.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau des comptes -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Comptes de la plateforme (lecture seule)</caption>
                <thead>
                    <tr>
                        <th scope="col">Utilisateur</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Ville</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Inscrit</th>
                        <th scope="col">Annonces</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                        <?php
                        $nomComplet = trim(
                            (string) ($utilisateur['prenom'] ?? '')
                            . ' '
                            . (string) ($utilisateur['nom'] ?? '')
                        );
                        ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($nomComplet) ?></span>
                                <span class="block text-xs text-stone-500"><?= $esc($utilisateur['email'] ?? '') ?></span>
                            </td>
                            <td>
                                <span class="dash-badge dash-badge--neutre">
                                    <code><?= $esc($utilisateur['role'] ?? '') ?></code>
                                </span>
                            </td>
                            <td class="text-stone-500"><?= $esc($utilisateur['ville_nom'] ?? '—') ?></td>
                            <td><?= dashBadgeStatut((string) ($utilisateur['status'] ?? '')) ?></td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashDateFr($utilisateur['created_at'] ?? null)) ?>
                            </td>
                            <td><?= (int) ($utilisateur['nb_annonces'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    La modification des rôles et des statuts de compte relève de l'espace administrateur :
    le rôle modérateur dispose d'un accès en consultation uniquement.
</p>
