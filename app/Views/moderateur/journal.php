<?php
/**
 * Vue : journal personnel des actions de modération (GET /moderateur/journal).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::journal()
 * (palier 7.2 — lecture seule, données réelles de la base) :
 *   $journal : entrées du journal d'audit du modérateur connecté, colonnes du
 *              modèle AuditLog (action, description, target_type, ip_address,
 *              created_at)
 *
 * Le journal est PERSONNEL : il ne contient que les actions du modérateur
 * connecté. Aucun accès SQL ici. Aucune valeur n'est affichée sans
 * échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

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

<?php if ($journal === []): ?>
    <!-- État vide : aucune action enregistrée pour ce modérateur -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucune action enregistrée</p>
            <p>Vos actions de modération apparaîtront ici.</p>
        </div>
    </section>
<?php else: ?>
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
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashTempsRelatif($entree['created_at'] ?? null)) ?>
                            </td>
                            <td class="font-medium text-stone-900"><?= $esc($entree['action'] ?? '') ?></td>
                            <td><?= $esc($entree['description'] ?? '') ?></td>
                            <td>
                                <span class="dash-badge dash-badge--neutre">
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
    Ce journal ne contient que vos propres actions de modération. Il est conservé
    pour la traçabilité et n'est pas modifiable depuis cette interface.
</p>
