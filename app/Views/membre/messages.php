<?php
/**
 * Vue : messagerie du membre (GET /membre/messages).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::messages()
 * (palier 7.1 — lecture seule, données réelles de la base) :
 *   $conversations       : Message::listerConversations() — une conversation
 *                          = (annonces_id, interlocuteur_id) ; colonnes
 *                          annonce_titre, interlocuteur_prenom/nom,
 *                          dernier_message, non_lus, nb_messages
 *   $nonLus              : Message::compterNonLus() (messages non lus)
 *   $fil                 : fil de la conversation ouverte (annonce, contact,
 *                          messages[] avec auteur, contenu, date)
 *   $conversationOuverte : couple (annonces_id, interlocuteur_id) affiché
 *
 * LECTURE SEULE : aucun envoi de message (le formulaire reste désactivé).
 * Les paramètres d'URL `annonce` et `interlocuteur` ne déterminent PAS la
 * propriété des données : elle provient toujours du membre authentifié.
 *
 * Aucune requête SQL ici ; toute valeur est échappée à l'affichage.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
?>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mes messages</h2>
        <p class="dash-section-desc">
            <?= (int) $nonLus ?> message<?= ((int) $nonLus) > 1 ? 's' : '' ?> non lu<?= ((int) $nonLus) > 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3">

    <!-- Liste des conversations -->
    <section class="dash-card dash-card--flush lg:col-span-1">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">Conversations</h3>
                <p class="dash-card-subtitle"><?= count($conversations) ?> au total</p>
            </div>
            <?php if ((int) $nonLus > 0): ?>
                <span class="dash-count dash-count--attente"><?= (int) $nonLus ?></span>
            <?php endif; ?>
        </div>

        <?php if ($conversations === []): ?>
            <!-- État vide : aucune conversation -->
            <div class="dash-empty">
                <p class="dash-empty-title">Aucune conversation</p>
                <p>Vos échanges avec les autres membres apparaîtront ici.</p>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-stone-100">
                <?php foreach ($conversations as $conversation): ?>
                    <?php
                    $nbNonLus  = (int) ($conversation['non_lus'] ?? 0);
                    $nbMessages = (int) ($conversation['nb_messages'] ?? 0);
                    // La conversation ouverte est mise en évidence (lien actif).
                    $estOuverte = $conversationOuverte !== null
                        && (string) ($conversationOuverte['annonces_id'] ?? '') === (string) ($conversation['annonces_id'] ?? '')
                        && (string) ($conversationOuverte['interlocuteur_id'] ?? '') === (string) ($conversation['interlocuteur_id'] ?? '');
                    $nomInterlocuteur = trim(
                        (string) ($conversation['interlocuteur_prenom'] ?? '')
                        . ' '
                        . (string) ($conversation['interlocuteur_nom'] ?? '')
                    );
                    // Lecture seule : le lien ne fait que recharger la page avec
                    // le couple (annonce, interlocuteur) à afficher dans le fil.
                    $lienFil = base_path('membre/messages')
                        . '?annonce=' . rawurlencode((string) ($conversation['annonces_id'] ?? ''))
                        . '&interlocuteur=' . rawurlencode((string) ($conversation['interlocuteur_id'] ?? ''));
                    ?>
                    <li class="<?= $estOuverte ? 'bg-stone-50' : '' ?>">
                        <a class="block px-5 py-3.5" href="<?= $esc($lienFil) ?>">
                            <div class="flex items-start gap-3">
                                <span
                                    class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full <?= $nbNonLus > 0 ? 'bg-amber-400' : 'bg-stone-300' ?>"
                                    aria-hidden="true"
                                ></span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-stone-900">
                                        <?= $esc($nomInterlocuteur) ?>
                                    </p>
                                    <p class="truncate text-xs font-medium text-teal-700">
                                        <?= $esc($conversation['annonce_titre'] ?? '') ?>
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-stone-500">
                                        <?= $nbMessages ?> message<?= $nbMessages > 1 ? 's' : '' ?>
                                        <?php if ($nbNonLus > 0): ?>
                                            · <span class="font-semibold text-amber-600"><?= $nbNonLus ?> non lu<?= $nbNonLus > 1 ? 's' : '' ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mt-1 text-xs text-stone-400">
                                        <?= $esc(dashTempsRelatif($conversation['dernier_message'] ?? null)) ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- Volet de lecture -->
    <section class="dash-card lg:col-span-2">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title">
                    <?= $esc($fil['contact'] !== '' ? $fil['contact'] : 'Conversation') ?>
                </h3>
                <p class="dash-card-subtitle">
                    <?= $esc($fil['annonce'] !== '' ? $fil['annonce'] : 'Sélectionnez une conversation') ?>
                </p>
            </div>
            <?php if ($fil['contact'] !== ''): ?>
                <span class="dash-badge dash-badge--actif">Conversation ouverte</span>
            <?php endif; ?>
        </div>

        <?php if (($fil['messages'] ?? []) === []): ?>
            <!-- État vide : aucune conversation sélectionnée ou fil vide -->
            <div class="dash-empty">
                <p class="dash-empty-title">Aucun message</p>
                <p>Sélectionnez une conversation pour afficher les échanges.</p>
            </div>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($fil['messages'] as $messageFil): ?>
                    <?php $estMoi = ($messageFil['auteur'] ?? '') === 'moi'; ?>
                    <li class="flex <?= $estMoi ? 'justify-end' : 'justify-start' ?>">
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 <?= $estMoi ? 'bg-teal-600 text-white' : 'bg-stone-100 text-stone-800' ?>">
                            <p class="text-sm"><?= $esc($messageFil['contenu'] ?? '') ?></p>
                            <p class="mt-1 text-xs <?= $estMoi ? 'text-teal-100' : 'text-stone-500' ?>">
                                <?= $esc(dashTempsRelatif($messageFil['date'] ?? null)) ?>
                            </p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Zone de réponse : DÉSACTIVÉE (palier 7.1 en lecture seule) -->
        <?php if ($fil['contact'] !== ''): ?>
            <div class="mt-5 border-t border-stone-100 pt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500" for="message-reponse">
                    Votre réponse
                </label>
                <textarea
                    id="message-reponse"
                    name="message"
                    rows="2"
                    class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm text-stone-500 disabled:cursor-not-allowed"
                    placeholder="L'envoi de messages sera disponible dans une prochaine étape"
                    disabled
                ></textarea>

                <div class="mt-3 flex justify-end">
                    <button type="button" class="dash-btn dash-btn--primary" disabled title="Disponible dans une prochaine étape">
                        <?= dashIcon('fleche') ?>
                        <span>Envoyer</span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
