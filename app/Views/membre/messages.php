<?php
/**
 * Vue : messagerie du membre (GET /membre/messages).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::messages() :
 *   $conversations : liste des conversations (expediteur, sujet, extrait, date, non_lu)
 *   $nonLus        : nombre de conversations non lues
 *   $fil           : fil de discussion affiché dans le volet de lecture
 *                    (annonce, contact, messages[] avec auteur, contenu, date)
 *
 * Données statiques (étape 2) : aucun accès base de données.
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
        <span class="font-semibold text-stone-900">Messagerie de démonstration.</span>
        L'envoi et la lecture réels (table <code>messages</code>) seront activés à l'étape 7.
    </p>
</div>

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

        <ul class="divide-y divide-stone-100">
            <?php foreach ($conversations as $index => $conversation): ?>
                <?php $nonLu = ($conversation['non_lu'] ?? false) === true; ?>
                <li class="<?= $index === 0 ? 'bg-stone-50' : '' ?> px-5 py-3.5">
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full <?= $nonLu ? 'bg-amber-400' : 'bg-stone-300' ?>"
                            aria-hidden="true"
                        ></span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-stone-900">
                                <?= $esc($conversation['expediteur'] ?? '') ?>
                            </p>
                            <p class="truncate text-xs font-medium text-teal-700">
                                <?= $esc($conversation['sujet'] ?? '') ?>
                            </p>
                            <p class="mt-0.5 truncate text-xs text-stone-500">
                                <?= $esc($conversation['extrait'] ?? '') ?>
                            </p>
                            <p class="mt-1 text-xs text-stone-400">
                                <?= $esc($conversation['date'] ?? '') ?>
                            </p>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- Volet de lecture -->
    <section class="dash-card lg:col-span-2">
        <div class="dash-card-head">
            <div>
                <h3 class="dash-card-title"><?= $esc($fil['contact'] ?? '') ?></h3>
                <p class="dash-card-subtitle"><?= $esc($fil['annonce'] ?? '') ?></p>
            </div>
            <span class="dash-badge dash-badge--actif">Conversation ouverte</span>
        </div>

        <ul class="space-y-4">
            <?php foreach (($fil['messages'] ?? []) as $messageFil): ?>
                <?php $estMoi = ($messageFil['auteur'] ?? '') === 'moi'; ?>
                <li class="flex <?= $estMoi ? 'justify-end' : 'justify-start' ?>">
                    <div class="max-w-[85%] rounded-2xl px-4 py-2.5 <?= $estMoi ? 'bg-teal-600 text-white' : 'bg-stone-100 text-stone-800' ?>">
                        <p class="text-sm"><?= $esc($messageFil['contenu'] ?? '') ?></p>
                        <p class="mt-1 text-xs <?= $estMoi ? 'text-teal-100' : 'text-stone-500' ?>">
                            <?= $esc($messageFil['date'] ?? '') ?>
                        </p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Zone de réponse (non active : étape 7) -->
        <div class="mt-5 border-t border-stone-100 pt-4">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500" for="message-reponse">
                Votre réponse
            </label>
            <textarea
                id="message-reponse"
                name="message"
                rows="2"
                class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm text-stone-500 disabled:cursor-not-allowed"
                placeholder="L'envoi de messages sera activé à l'étape 7"
                disabled
            ></textarea>

            <div class="mt-3 flex justify-end">
                <button type="button" class="dash-btn dash-btn--primary" disabled title="Disponible à l'étape 7">
                    <?= dashIcon('fleche') ?>
                    <span>Envoyer</span>
                </button>
            </div>
        </div>
    </section>
</div>
