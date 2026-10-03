<?php
/**
 * Vue : administration des comptes (GET /admin/utilisateurs).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::utilisateurs()
 * (palier 7.3 — données réelles de MariaDB, LECTURE SEULE) :
 *   $utilisateurs : comptes, colonnes du modèle User (prenom, nom, email,
 *                   role, telephone, ville_nom, status, created_at,
 *                   nb_annonces)
 *   $total        : nombre total de comptes (COUNT users)
 *   $repartition  : comptes par rôle technique (role, total)
 *
 * SEUL espace habilité à administrer les rôles et les statuts de compte
 * (le rôle modérateur n'a qu'un accès en consultation, étape 7.2).
 * À ce palier, aucune écriture n'est possible : les boutons d'action sont
 * DÉSACTIVÉS (disabled, type="button", aucun formulaire, aucun POST) et
 * n'effectuent donc aucune opération.
 *
 * Le mot de passe n'est JAMAIS sélectionné par le modèle : ni
 * `password_hash`, ni jeton, ni donnée d'authentification ne peut être
 * affiché ici.
 *
 * Le rôle technique est affiché tel quel ('member', 'moderateur', 'admin'),
 * sans créer de second référentiel de libellés de rôle.
 *
 * Aucune requête SQL ici. Aucune valeur n'est affichée sans échappement HTML.
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
        Les comptes affichés proviennent de la table <code>users</code>. Aucune
        création, modification, suppression, changement de rôle ou blocage n'est
        possible à ce stade&nbsp;: les actions resteront inactives jusqu'à leur
        implémentation validée côté serveur (avec CSRF et journalisation).
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Utilisateurs</h2>
        <p class="dash-section-desc">
            <?= (int) $total ?> compte<?= (int) $total > 1 ? 's' : '' ?>
            sur la plateforme — lecture seule
        </p>
    </div>

    <span class="dash-badge dash-badge--neutre">Lecture seule</span>
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

<?php if ($utilisateurs === []): ?>
    <!-- État vide : aucun compte en base -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucun utilisateur trouvé</p>
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
                        <th scope="col">Téléphone</th>
                        <th scope="col">Ville</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Inscrit</th>
                        <th scope="col">Annonces</th>
                        <th scope="col">Administration</th>
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
                        $actif = ($utilisateur['status'] ?? '') === 'active';
                        ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900">
                                    <?= $esc($nomComplet !== '' ? $nomComplet : '—') ?>
                                </span>
                                <span class="block text-xs text-stone-500">
                                    <?= $esc($utilisateur['email'] ?? '') ?>
                                </span>
                            </td>
                            <td>
                                <span class="dash-badge dash-badge--neutre">
                                    <code><?= $esc($utilisateur['role'] ?? '') ?></code>
                                </span>
                            </td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc($utilisateur['telephone'] ?? '—') ?>
                            </td>
                            <td class="text-stone-500"><?= $esc($utilisateur['ville_nom'] ?? '—') ?></td>
                            <td><?= dashBadgeStatut((string) ($utilisateur['status'] ?? '')) ?></td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashDateFr($utilisateur['created_at'] ?? null)) ?>
                            </td>
                            <td><?= (int) ($utilisateur['nb_annonces'] ?? 0) ?></td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('parametres') ?><span>Changer le rôle</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon($actif ? 'refuser' : 'valider') ?>
                                        <span><?= $actif ? 'Suspendre' : 'Réactiver' ?></span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
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
<?php endif; ?>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    Chaque modification de rôle ou de statut devra être validée côté serveur, protégée par un
    jeton CSRF et journalisée dans <code>audit_logs</code>. Un administrateur ne pourra pas
    modifier son propre compte (protection contre la perte d'accès).
</p>

