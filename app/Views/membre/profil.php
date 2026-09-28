<?php
/**
 * Vue : profil du membre (GET /membre/profil).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * Données fournies par App\Controllers\MembreController::profil() :
 *   $profil   : informations affichées (prenom, nom, email, telephone,
 *               ville, membre_depuis, statut)
 *   $activite : chiffres d'activité (label, valeur)
 *
 * Les champs sont en LECTURE SEULE : aucun formulaire actif, donc aucune
 * donnée ne peut être envoyée. L'édition réelle (validation serveur,
 * hachage du mot de passe, écriture en base) est prévue à l'étape 7.
 *
 * Aucune valeur utilisateur n'est affichée sans échappement HTML.
 */

$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');

// Classes internes (constantes du code, pas des données utilisateur)
$champClasse = 'w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm text-stone-600 disabled:cursor-not-allowed';
$labelClasse = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500';

$nomComplet = trim(($profil['prenom'] ?? '') . ' ' . ($profil['nom'] ?? ''));
?>

<!-- Avertissement de démonstration -->
<div class="mb-5 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4">
    <span class="mt-0.5 text-amber-500">
        <?= dashIcon('signalements') ?>
    </span>
    <p class="text-sm text-stone-700">
        <span class="font-semibold text-stone-900">Profil en lecture seule.</span>
        Le prénom affiché provient de votre session ; les autres champs sont des valeurs
        de démonstration remplacées par la table <code>users</code> à l'étape 7.
    </p>
</div>

<!-- En-tête de section -->
<div class="dash-section-head">
    <div>
        <h2 class="dash-section-title">Mon profil</h2>
        <p class="dash-section-desc">Vos informations personnelles et votre activité</p>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3">

    <!-- Colonne de gauche : identité et activité -->
    <div class="space-y-4">

        <section class="dash-card text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-teal-600 to-teal-900 font-poppins text-lg font-bold text-white">
                <?= $esc(dashInitiales((string) ($profil['prenom'] ?? ''), (string) ($profil['nom'] ?? ''))) ?>
            </span>

            <p class="mt-3 font-poppins text-base font-semibold text-stone-900">
                <?= $esc($nomComplet) ?>
            </p>

            <p class="mt-2">
                <?= dashBadgeStatut((string) ($profil['statut'] ?? '')) ?>
            </p>

            <p class="mt-2 text-xs text-stone-500">
                <?= $esc($profil['membre_depuis'] ?? '') ?>
            </p>
        </section>

        <section class="dash-card">
            <div class="dash-card-head">
                <div>
                    <h3 class="dash-card-title">Mon activité</h3>
                    <p class="dash-card-subtitle">Depuis la création du compte</p>
                </div>
            </div>

            <ul class="space-y-3">
                <?php foreach ($activite as $element): ?>
                    <li class="flex items-center justify-between">
                        <span class="text-sm text-stone-600"><?= $esc($element['label'] ?? '') ?></span>
                        <span class="font-poppins text-sm font-bold text-stone-900">
                            <?= $esc($element['valeur'] ?? '') ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <!-- Colonne de droite : informations et sécurité -->
    <div class="space-y-4 lg:col-span-2">

        <section class="dash-card">
            <div class="dash-card-head">
                <div>
                    <h3 class="dash-card-title">Informations personnelles</h3>
                    <p class="dash-card-subtitle">Champs en lecture seule pour le moment</p>
                </div>
                <span class="dash-badge dash-badge--attente">Lecture seule</span>
            </div>

            <fieldset class="grid gap-4 sm:grid-cols-2" disabled>
                <div>
                    <label class="<?= $labelClasse ?>" for="profil-prenom">Prénom</label>
                    <input class="<?= $champClasse ?>" type="text" id="profil-prenom" name="prenom"
                           value="<?= $esc($profil['prenom'] ?? '') ?>" maxlength="80">
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-nom">Nom</label>
                    <input class="<?= $champClasse ?>" type="text" id="profil-nom" name="nom"
                           value="<?= $esc($profil['nom'] ?? '') ?>" maxlength="80">
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-email">Adresse email</label>
                    <input class="<?= $champClasse ?>" type="email" id="profil-email" name="email"
                           value="<?= $esc($profil['email'] ?? '') ?>" maxlength="191">
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-telephone">Téléphone</label>
                    <input class="<?= $champClasse ?>" type="tel" id="profil-telephone" name="telephone"
                           value="<?= $esc($profil['telephone'] ?? '') ?>" maxlength="20">
                </div>

                <div class="sm:col-span-2">
                    <label class="<?= $labelClasse ?>" for="profil-ville">Ville</label>
                    <input class="<?= $champClasse ?>" type="text" id="profil-ville" name="ville"
                           value="<?= $esc($profil['ville'] ?? '') ?>" maxlength="100">
                </div>
            </fieldset>
        </section>

        <section class="dash-card">
            <div class="dash-card-head">
                <div>
                    <h3 class="dash-card-title">Sécurité</h3>
                    <p class="dash-card-subtitle">Mot de passe du compte</p>
                </div>
                <span class="dash-badge dash-badge--attente">Lecture seule</span>
            </div>

            <fieldset class="grid gap-4 sm:grid-cols-2" disabled>
                <div>
                    <label class="<?= $labelClasse ?>" for="profil-motdepasse">Mot de passe actuel</label>
                    <input class="<?= $champClasse ?>" type="password" id="profil-motdepasse"
                           name="password_actuel" value="" placeholder="••••••••" autocomplete="current-password">
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-nouveau">Nouveau mot de passe</label>
                    <input class="<?= $champClasse ?>" type="password" id="profil-nouveau"
                           name="password_nouveau" value="" placeholder="••••••••" autocomplete="new-password">
                </div>
            </fieldset>

            <p class="mt-4 text-xs text-stone-500">
                Le changement de mot de passe sera activé à l'étape 7 : validation côté serveur,
                hachage avec <code>password_hash()</code> et protection CSRF.
            </p>
        </section>
    </div>

</div>
