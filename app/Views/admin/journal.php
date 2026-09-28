<?php
/**
 * Vue : journal d'audit complet (GET /admin/journal).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::journal() :
 *   $journal : entrées (date, utilisateur, role, action, cible, type, ip)
 *
 * Différence avec le journal du modérateur (étape 3) : l'administrateur voit
 * les actions de TOUS les comptes, tous rôles confondus.
 *
 * Journal en LECTURE SEULE : il est alimenté par le serveur et ne doit jamais
 * pouvoir être modifié depuis l'interface.
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
        <span class="font-semibold text-stone-900">Journal de démonstration.</span>
        Les entrées réelles de la table <code>audit_logs</code> seront chargées à l'étape 7.
        L'écriture y sera faite exclusivement côté serveur.
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

<!-- Tableau du journal -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Journal d'audit de la plateforme</caption>
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
                        <td class="whitespace-nowrap"><code><?= $esc($entree['ip'] ?? '') ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Rappel du périmètre du rôle -->
<p class="mt-4 text-xs text-stone-500">
    Le journal d'audit est immuable : aucune entrée ne peut être créée, modifiée ou supprimée
    depuis l'interface. Sa consultation est réservée au rôle administrateur.
</p>
