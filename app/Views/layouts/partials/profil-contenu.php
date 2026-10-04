<?php
/**
 * Partial commun : contenu de la page « Mon profil » des trois espaces
 * connectés (membre, modérateur, administrateur).
 *
 * UTILISÉ PAR :
 *   - app/Views/membre/profil.php     (GET /membre/profil)
 *   - app/Views/moderateur/profil.php (GET /moderateur/profil)
 *   - app/Views/admin/profil.php      (GET /admin/profil)
 *
 * VARIABLES ATTENDUES (toutes facultatives, avec repli sûr) :
 *   $profil   array<string, mixed> : ligne `users` de l'utilisateur CONNECTÉ,
 *              préparée par le contrôleur (User::trouverParId sur l'identifiant
 *              de SESSION `user_id`) : prenom, nom, email, telephone, ville,
 *              role, statut, created_at, avatar, email_verifie.
 *   $activite array<int, array{label: string, valeur: mixed}> : statistiques
 *              d'activité réelles, propres au rôle (aucune donnée inventée).
 *
 * RÈGLES RESPECTÉES
 *   - AUCUNE requête SQL, AUCUN accès PDO, AUCUN modèle : ce fichier ne
 *     fait QUE de l'affichage des données déjà préparées par le contrôleur ;
 *   - l'utilisateur affiché est TOUJOURS l'utilisateur connecté : aucun
 *     identifiant n'est lu dans $_GET, $_POST, $_REQUEST ni dans l'URL ;
 *   - LECTURE SEULE stricte : aucun <form>, aucun champ actif, aucune route
 *     POST. Les <fieldset> sont explicitement « disabled » ;
 *   - aucune donnée sensible : `password_hash` n'est jamais transmis par les
 *     contrôleurs, il ne peut donc pas être affiché ici ;
 *   - le rôle affiché provient de App\Core\Auth::label() (référentiel unique
 *     des libellés), jamais d'un dictionnaire parallèle ;
 *   - design strictement identique à celui de l'ancien profil membre : mêmes
 *     classes CSS, aucune feuille de style ni librairie nouvelle ;
 *   - aucune valeur utilisateur n'est affichée sans échappement HTML.
 */

use App\Core\Auth;

// Échappement unique réutilisé pour toutes les données affichées
$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');

// --- Contexte du compte affiché ------------------------------------------
// `role` est la valeur technique de l'ENUM `users.role` ('member',
// 'moderateur', 'admin'). `Auth::label()` fournit son libellé français ; un
// rôle inconnu donne une chaîne vide, jamais une traduction inventée.
$roleCode = isset($profil['role']) && is_string($profil['role']) ? trim($profil['role']) : '';
$roleLabel = Auth::label($roleCode === '' ? null : $roleCode);

// Valeurs d'affichage robustes : toute donnée absente devient « — », afin
// qu'aucune information ne soit jamais inventée.
$valeur = static fn (mixed $donnee): string => (is_string($donnee) && trim($donnee) !== '')
    ? trim($donnee)
    : '—';

$nomComplet = $valeur(trim(($profil['prenom'] ?? '') . ' ' . ($profil['nom'] ?? '')));

// Avatar : exploitable uniquement s'il s'agit d'une URL absolue http(s) ou
// d'un chemin absolu serveur (même règle que dashAvatar()). À défaut,
// initiales via dashInitiales(). Aucune URL n'est inventée.
$avatarBrut = isset($profil['avatar']) && is_string($profil['avatar']) ? trim($profil['avatar']) : '';
$avatarExploitable = $avatarBrut !== ''
    && (preg_match('#^https?://#i', $avatarBrut) === 1 || str_starts_with($avatarBrut, '/'));

// Vérification d'adresse e-mail : donnée informative uniquement. Aucune
// autorisation n'en dépend (les rôles sont décidés par RoleMiddleware).
$emailVerifie = isset($profil['email_verifie']) ? (int) $profil['email_verifie'] : 0;

// Libellé de la date d'inscription, adapté au rôle affiché. Le membre
// conserve exactement l'intitulé historique « Membre depuis le … ».
$roleInscription = $roleLabel !== '' ? $roleLabel : 'Compte';

// Classes internes (constantes du code, pas des données utilisateur) :
// identiques à celles de l'ancien profil membre.
$champClasse = 'w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm text-stone-600 disabled:cursor-not-allowed';
$labelClasse = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500';

// Statistiques d'activité : repli sur un tableau vide si le contrôleur n'en
// fournit aucune (le bloc « Mon activité » reste alors simplement vide).
$activite = isset($activite) && is_array($activite) ? $activite : [];
?>
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
            <?php if ($avatarExploitable): ?>
                <?= $esc(dashAvatar($avatarBrut, (string) ($profil['prenom'] ?? ''), (string) ($profil['nom'] ?? ''))) ?>
            <?php else: ?>
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-teal-600 to-teal-900 font-poppins text-lg font-bold text-white">
                    <?= $esc(dashInitiales((string) ($profil['prenom'] ?? ''), (string) ($profil['nom'] ?? ''))) ?>
                </span>
            <?php endif; ?>

            <p class="mt-3 font-poppins text-base font-semibold text-stone-900">
                <?= $esc($nomComplet) ?>
            </p>

            <p class="mt-2">
                <?= dashBadgeStatut((string) ($profil['statut'] ?? '')) ?>
            </p>

            <?php if ($roleLabel !== ''): ?>
                <p class="mt-2 text-xs font-semibold text-stone-600">
                    <?= $esc($roleLabel) ?>
                </p>
            <?php endif; ?>

            <p class="mt-2 text-xs text-stone-500">
                <?= $esc($roleInscription) ?> depuis le <?= $esc(dashDateFr($profil['created_at'] ?? null)) ?>
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
                    <?php
                    if (!is_array($element)) {
                        continue;
                    }
                    ?>
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
                           value="<?= $esc($profil['prenom'] ?? '') ?>" maxlength="80" readonly>
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-nom">Nom</label>
                    <input class="<?= $champClasse ?>" type="text" id="profil-nom" name="nom"
                           value="<?= $esc($profil['nom'] ?? '') ?>" maxlength="80" readonly>
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-email">Adresse email</label>
                    <input class="<?= $champClasse ?>" type="email" id="profil-email" name="email"
                           value="<?= $esc($profil['email'] ?? '') ?>" maxlength="191" readonly>
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-telephone">Téléphone</label>
                    <input class="<?= $champClasse ?>" type="tel" id="profil-telephone" name="telephone"
                           value="<?= $esc($valeur($profil['telephone'] ?? null)) ?>" maxlength="20" readonly>
                </div>

                <div class="sm:col-span-2">
                    <label class="<?= $labelClasse ?>" for="profil-ville">Ville</label>
                    <input class="<?= $champClasse ?>" type="text" id="profil-ville" name="ville"
                           value="<?= $esc($valeur($profil['ville'] ?? null)) ?>" maxlength="100" readonly>
                </div>
            </fieldset>

            <p class="mt-4 text-xs text-stone-500">
                Rôle du compte :
                <span class="font-semibold text-stone-600">
                    <?= $esc($roleLabel !== '' ? $roleLabel . ' (' . $roleCode . ')' : '—') ?>
                </span>
                — adresse email <?= $emailVerifie === 1 ? 'vérifiée' : 'non vérifiée' ?>.
            </p>
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
                           name="password_actuel" value="" placeholder="••••••••" autocomplete="current-password" readonly>
                </div>

                <div>
                    <label class="<?= $labelClasse ?>" for="profil-nouveau">Nouveau mot de passe</label>
                    <input class="<?= $champClasse ?>" type="password" id="profil-nouveau"
                           name="password_nouveau" value="" placeholder="••••••••" autocomplete="new-password" readonly>
                </div>
            </fieldset>

            <p class="mt-4 text-xs text-stone-500">
                Le changement de mot de passe sera disponible dans une prochaine étape : validation
                côté serveur, hachage avec <code>password_hash()</code> et protection CSRF.
            </p>
        </section>
    </div>

</div>