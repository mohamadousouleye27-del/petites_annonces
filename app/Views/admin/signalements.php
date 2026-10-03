<?php
/**
 * Vue : vue globale des signalements (GET /admin/signalements).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\AdminController::signalements()
 * (palier 7.3 — données réelles de MariaDB, LECTURE SEULE) :
 *   $signalements : liste, colonnes du modèle Signalement (annonce_titre,
 *                   auteur_prenom, auteur_nom, signaleur_prenom,
 *                   signaleur_nom, raison, status, created_at, resolved_at,
 *                   resolveur_prenom, resolveur_nom)
 *   $compteurs    : total, en_attente, traite, rejete
 *
 * Différence avec l'espace modérateur (palier 7.2) : la vue est globale et
 * affiche la TRAÇABILITÉ (`resolved_by`, `resolved_at`), sans aucune action
 * de traitement — ni traiter, ni rejeter, ni assigner, ni résoudre, ni
 * supprimer. Les boutons sont DÉSACTIVÉS.
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
        Les signalements proviennent de la table <code>signalements</code>, avec les
        statuts réellement enregistrés (<code>en_attente</code>, <code>traite</code>,
        <code>rejete</code>) et leur traçabilité
        (<code>resolved_by</code>, <code>resolved_at</code>). Aucun traitement n'est
        possible à ce stade.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Signalements</h2>
        <p class="dash-section-desc">
            Vue globale — <?= (int) $compteurs['en_attente'] ?> en attente
            sur <?= (int) $compteurs['total'] ?> signalement(s)
        </p>
    </div>

    <span class="dash-badge dash-badge--neutre">Traçabilité complète</span>
</div>

<!-- Compteurs par statut -->
<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="dash-count">Total <strong class="ml-1"><?= (int) $compteurs['total'] ?></strong></span>
    <span class="dash-badge dash-badge--attente"><?= (int) $compteurs['en_attente'] ?> en attente</span>
    <span class="dash-badge dash-badge--actif"><?= (int) $compteurs['traite'] ?> traité(s)</span>
    <span class="dash-badge dash-badge--rejete"><?= (int) $compteurs['rejete'] ?> rejeté(s)</span>
</div>

<?php if ($signalements === []): ?>
    <!-- État vide : aucun signalement en base -->
    <section class="dash-card">
        <div class="dash-empty">
            <p class="dash-empty-title">Aucun signalement trouvé</p>
            <p>Les signalements déposés par les membres apparaîtront ici.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau des signalements -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Signalements de la plateforme et traçabilité (lecture seule)</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Motif</th>
                        <th scope="col">Signalé par</th>
                        <th scope="col">Reçu</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Traité par</th>
                        <th scope="col">Traité le</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($signalements as $signalement): ?>
                        <?php
                        $auteur = trim(
                            (string) ($signalement['auteur_prenom'] ?? '')
                            . ' '
                            . (string) ($signalement['auteur_nom'] ?? '')
                        );
                        $signaleur = trim(
                            (string) ($signalement['signaleur_prenom'] ?? '')
                            . ' '
                            . (string) ($signalement['signaleur_nom'] ?? '')
                        );
                        $resolveur = trim(
                            (string) ($signalement['resolveur_prenom'] ?? '')
                            . ' '
                            . (string) ($signalement['resolveur_nom'] ?? '')
                        );
                        $traite = ($resolveur !== '') || (($signalement['resolved_at'] ?? null) !== null);
                        ?>
                        <tr>
                            <td>
                                <span class="font-medium text-stone-900"><?= $esc($signalement['annonce_titre'] ?? '') ?></span>
                                <span class="block text-xs text-stone-500">
                                    Propriétaire : <?= $esc($auteur !== '' ? $auteur : '—') ?>
                                </span>
                            </td>
                            <td><?= $esc($signalement['raison'] ?? '') ?></td>
                            <td><?= $esc($signaleur !== '' ? $signaleur : '—') ?></td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashTempsRelatif($signalement['created_at'] ?? null)) ?>
                            </td>
                            <td><?= dashBadgeStatut((string) ($signalement['status'] ?? '')) ?></td>
                            <td>
                                <?php if ($traite): ?>
                                    <span class="font-medium text-stone-900">
                                        <?= $esc($resolveur !== '' ? $resolveur : '—') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-stone-400">Non traité</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc(dashDateFr($signalement['resolved_at'] ?? null)) ?>
                            </td>
                            <td>
                                <div class="dash-actions">
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('valider') ?><span>Traiter</span>
                                    </button>
                                    <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" disabled title="Non fonctionnel à ce stade (lecture seule)">
                                        <?= dashIcon('refuser') ?><span>Rejeter</span>
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
    Le traitement des signalements (statut, <code>resolved_by</code>, <code>resolved_at</code>)
    relève des modérateurs ; l'administrateur dispose ici d'une vue de contrôle globale,
    sans action de décision.
</p>
