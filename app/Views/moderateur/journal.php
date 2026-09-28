<?php
/**
 * Vue : journal des actions de modération (GET /moderateur/journal).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::journal() :
 *   $journal : actions (date, action, cible, type, ip)
 *
 * Le journal est en LECTURE SEULE : il reflète les actions déjà effectuées.
 * L'écriture dans `audit_logs` sera réalisée côté serveur à l'étape 7.
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
        <span class="font-semibold text-stone-900">Journal de démonstration.</span>
        Les entrées réelles proviendront de la table <code>audit_logs</code>
        (<code>action</code>, <code>description</code>, <code>ip_address</code>,
        <code>target_type</code>) à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mon journal</h2>
        <p class="dash-section-desc">
            <?= count($journal) ?> action<?= count($journal) > 1 ? 's' : '' ?> de modération enregistrée<?= count($journal) > 1 ? 's' : '' ?>
        </p>
    </div>

    <span class="dash-badge dash-badge--neutre">Lecture seule</span>
</div>

<!-- Tableau du journal -->
<section class="dash-card dash-card--flush">
    <div class="dash-table-wrapper">
        <table class="dash-table">
            <caption class="sr-only">Actions de modération enregistrées</caption>
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Action</th>
                    <th scope="col">Cible</th>
                    <th scope="col">Type</th>
                    <th scope="col">Adresse IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($journal as $entree): ?>
                    <tr>
                        <td class="whitespace-nowrap text-stone-500"><?= $esc($entree['date'] ?? '') ?></td>
                        <td class="font-medium text-stone-900"><?= $esc($entree['action'] ?? '') ?></td>
                        <td><?= $esc($entree['cible'] ?? '') ?></td>
                        <td>
                            <span class="dash-badge dash-badge--neutre">
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
    Ce journal ne contient que vos propres actions de modération. Il est conservé
    pour la traçabilité et n'est pas modifiable depuis cette interface.
</p>
