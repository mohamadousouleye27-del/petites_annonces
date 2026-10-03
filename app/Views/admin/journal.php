<?php
/**
 * Vue : journal d'audit complet (GET /admin/journal).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::journal()
 * (palier 7.3 — données réelles de MariaDB, LECTURE SEULE) :
 *   $journal : entrées du journal d'audit GLOBAL, colonnes du modèle
 *              AuditLog (auteur_prenom, auteur_nom, auteur_role, action,
 *              description, target_type, ip_address, created_at)
 *
 * Différence avec le journal du modérateur (palier 7.2) : l'administrateur
 * voit les actions de TOUS les comptes, tous rôles confondus.
 *
 * Le schéma réel de `audit_logs` ne comporte AUCUNE colonne `target_id` :
 * la cible est identifiée par `description` et catégorisée par
 * `target_type`, conformément au modèle AuditLog. Aucune colonne n'est
 * inventée.
 *
 * Journal en LECTURE SEULE : il est alimenté par le serveur et ne doit
 * jamais pouvoir être modifié depuis l'interface.
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
        <span class="font-semibold text-stone-900">Journal réel, consultation seule.</span>
        Les entrées proviennent de la table <code>audit_logs</code> (colonnes
        <code>created_at</code>, <code>user_id</code>, <code>action</code>,
        <code>description</code>, <code>ip_address</code>, <code>target_type</code>).
        Aucune écriture n'est effectuée dans ce journal.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Journal d'audit</h2>
        <p class="dash-section-desc">
            <?= count($journal) ?> action<?= count($journal) > 1 ? 's' : '' ?> enregistrée<?= count($journal) > 1 ? 's' : '' ?>
            — tous rôles confondus
        </p>
    </div>

    <span class="dash-badge dash-badge--neutre">Lecture seule</span>
</div>

<?php if ($journal === []): ?>
    <!-- État vide : aucun audit enregistré -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune activité enregistrée</p>
            <p>Les actions de la plateforme apparaîtront ici.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau du journal -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Journal d'audit de la plateforme (lecture seule)</caption>
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Utilisateur</th>
                        <th scope="col">Action</th>
                        <th scope="col">Cible</th>
                        <th scope="col">Adresse IP</th>
                    </tr>
                </thead>
                <tbody>
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
                            <td class="whitespace-nowrap"><code><?= $esc($entree['ip_address'] ?? '—') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    Le journal d'audit est immuable : aucune entrée ne peut être créée, modifiée ou supprimée
    depuis l'interface. Sa consultation est réservée au rôle administrateur.
</p>
