<?php
/**
 * Vue : file des signalements (GET /moderateur/signalements).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\ModerateurController::signalements()
 * (palier 7.2 — lecture seule, données réelles de la base) :
 *   $signalements : colonnes du modèle Signalement (annonce_titre,
 *                   auteur_prenom/nom, signaleur_prenom/nom, raison, status,
 *                   created_at, resolved_at, resolveur_prenom/nom)
 *   $compteurs    : total, en_attente, traite, rejete (agrégats COUNT)
 *
 * Les boutons « Traiter » / « Rejeter » sont volontairement DÉSACTIVÉS : ce
 * palier est strictement en lecture et n'effectue AUCUNE écriture. Aucune
 * requête SQL ici. Aucune valeur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Signalements</h2>
        <p class="dash-section-desc">
            <?= (int) $compteurs['en_attente'] ?> en attente de traitement
            sur <?= (int) $compteurs['total'] ?> signalement(s)
        </p>
    </div>
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
            <p class="dash-empty-title">Aucun signalement</p>
            <p>Les signalements déposés par les membres apparaîtront ici.</p>
        </div>
    </section>
<?php else: ?>
    <!-- Tableau des signalements -->
    <section class="dash-card dash-card--flush">
        <div class="dash-table-wrapper">
            <table class="dash-table">
                <caption class="sr-only">Signalements déposés par les membres</caption>
                <thead>
                    <tr>
                        <th scope="col">Annonce</th>
                        <th scope="col">Motif</th>
                        <th scope="col">Signalé par</th>
                        <th scope="col">Reçu</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Traité par</th>
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
                        $traitable = ($signalement['status'] ?? '') === 'en_attente';
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
                            <td class="whitespace-nowrap text-stone-500">
                                <?= $esc($resolveur !== '' ? $resolveur : '—') ?>
                            </td>
                            <td>
                                <div class="dash-actions">
                                    <button
                                        type="button"
                                        class="dash-btn dash-btn--ghost dash-btn--sm"
                                        disabled
                                        title="<?= $traitable ? "Action disponible dans une prochaine étape" : 'Signalement déjà traité' ?>"
                                    >
                                        <?= dashIcon('valider') ?><span>Traiter</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="dash-btn dash-btn--ghost dash-btn--sm"
                                        disabled
                                        title="<?= $traitable ? "Action disponible dans une prochaine étape" : 'Signalement déjà traité' ?>"
                                    >
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
    Périmètre du rôle modérateur : traiter ou rejeter les signalements et statuer sur les
    annonces en attente. La gestion des comptes et des rôles relève de l'espace administrateur.
</p>
