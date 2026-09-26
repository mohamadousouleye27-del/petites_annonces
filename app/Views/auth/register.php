<?php
/**
 * Vue : formulaire d'inscription (GET /auth/register).
 *
 * Données fournies par App\Controllers\AuthController::renderRegisterForm() :
 *   $title        : titre de la page
 *   $old          : valeurs non sensibles saisies (prenom, nom, email, telephone)
 *   $errors       : erreurs de validation, indexées par champ
 *   $generalError : erreur technique générique (ou null)
 *   $csrfError    : erreur de protection CSRF (ou null)
 *
 * Aucune valeur utilisateur n'est affichée sans échappement HTML
 * (htmlspecialchars + ENT_QUOTES). Les mots de passe ne sont jamais
 * réaffichés.
 */

use App\Core\Csrf;

// Échappement systématique des valeurs affichées
$esc = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Récupère le message d'erreur (déjà échappé) d'un champ, ou null
$fieldError = static function (string $field) use ($errors, $esc): ?string {
    $message = $errors[$field] ?? null;

    return $message !== null ? $esc($message) : null;
};

$prenomError    = $fieldError('prenom');
$nomError       = $fieldError('nom');
$emailError     = $fieldError('email');
$telephoneError = $fieldError('telephone');
$passwordError  = $fieldError('password');
$termsError     = $fieldError('terms');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esc($title ?? 'Inscription') ?></title>
    <meta name="description" content="Créez gratuitement votre compte PetitesAnnonces.sn et publiez vos annonces partout au Sénégal.">

    <!-- Styles spécifiques aux pages d'authentification -->
    <link rel="stylesheet" href="<?= asset('assets/css/auth.css') ?>">
</head>
<body class="auth-page">

<!-- Décor de fond (même ambiance que le hero de l'accueil) -->
<div class="auth-grid-overlay" aria-hidden="true"></div>
<div class="auth-glow-1" aria-hidden="true"></div>
<div class="auth-glow-2" aria-hidden="true"></div>
<div class="auth-glow-3" aria-hidden="true"></div>

<main class="auth-shell">
    <!-- ============================================
         Panneau marque (visible sur desktop)
         ============================================ -->
    <section class="auth-brand">
        <a href="<?= base_path('/') ?>" class="auth-logo">
            <span class="auth-logo-mark" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </span>
            <span class="auth-logo-text">
                Petites<span class="auth-logo-amber">Annonces</span><span class="auth-logo-teal">.sn</span>
            </span>
        </a>

        <div>
            <h2 class="auth-brand-title">Rejoignez la communauté</h2>
            <p class="auth-brand-desc">
                Publiez vos annonces gratuitement et échangez en toute confiance
                à Dakar et partout au Sénégal.
            </p>

            <ul class="auth-brand-list">
                <li>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Publication gratuite et illimitée
                </li>
                <li>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Messagerie directe entre membres
                </li>
                <li>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Annonces vérifiées et modérées
                </li>
            </ul>
        </div>

        <p class="auth-brand-foot">© 2026 PetitesAnnonces.sn</p>
    </section>

    <!-- ============================================
         Panneau formulaire
         ============================================ -->
    <section class="auth-form-panel">
        <!-- En-tête mobile -->
        <div class="auth-mobile-head">
            <a href="<?= base_path('/') ?>" class="auth-mobile-logo">
                <span class="auth-logo-mark" aria-hidden="true">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </span>
                <span class="auth-logo-text">
                    Petites<span class="auth-logo-amber">Annonces</span><span class="auth-logo-teal">.sn</span>
                </span>
            </a>
            <a href="<?= base_path('auth/login') ?>" class="auth-head-link">Se connecter</a>
        </div>

        <h1 class="auth-title">Créer un compte</h1>
        <p class="auth-subtitle">
            Quelques informations suffisent pour commencer à publier vos annonces.
        </p>

        <!-- Erreur de protection CSRF -->
        <?php if ($csrfError !== null): ?>
            <div class="auth-alert auth-alert--error" role="alert">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.34 3.94l-8.14 14.1A1.5 1.5 0 003.5 20.25h17A1.5 1.5 0 0021.8 18.04L13.66 3.94a1.5 1.5 0 00-2.6 0z"/>
                </svg>
                <span><?= $esc($csrfError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Erreur technique générale -->
        <?php if ($generalError !== null): ?>
            <div class="auth-alert auth-alert--error" role="alert">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.34 3.94l-8.14 14.1A1.5 1.5 0 003.5 20.25h17A1.5 1.5 0 0021.8 18.04L13.66 3.94a1.5 1.5 0 00-2.6 0z"/>
                </svg>
                <span><?= $esc($generalError) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_path('auth/register') ?>" class="auth-form" novalidate>
            <?= Csrf::field() ?>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="prenom">Prénom</label>
                    <input
                        type="text"
                        id="prenom"
                        name="prenom"
                        class="auth-input<?= $prenomError !== null ? ' auth-input--error' : '' ?>"
                        value="<?= $esc($old['prenom'] ?? '') ?>"
                        placeholder="Ex : Aminata"
                        maxlength="80"
                        autocomplete="given-name"
                        required
                        <?= $prenomError !== null ? 'aria-invalid="true" aria-describedby="prenom-error"' : '' ?>
                    >
                    <?php if ($prenomError !== null): ?>
                        <p class="auth-field-error" id="prenom-error"><?= $prenomError ?></p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label for="nom">Nom</label>
                    <input
                        type="text"
                        id="nom"
                        name="nom"
                        class="auth-input<?= $nomError !== null ? ' auth-input--error' : '' ?>"
                        value="<?= $esc($old['nom'] ?? '') ?>"
                        placeholder="Ex : Diop"
                        maxlength="80"
                        autocomplete="family-name"
                        required
                        <?= $nomError !== null ? 'aria-invalid="true" aria-describedby="nom-error"' : '' ?>
                    >
                    <?php if ($nomError !== null): ?>
                        <p class="auth-field-error" id="nom-error"><?= $nomError ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="auth-field">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="auth-input<?= $emailError !== null ? ' auth-input--error' : '' ?>"
                    value="<?= $esc($old['email'] ?? '') ?>"
                    placeholder="Ex : aminata.diop@example.com"
                    maxlength="191"
                    autocomplete="email"
                    required
                    <?= $emailError !== null ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>
                >
                <?php if ($emailError !== null): ?>
                    <p class="auth-field-error" id="email-error"><?= $emailError ?></p>
                <?php endif; ?>
            </div>

            <div class="auth-field">
                <label for="telephone">Téléphone</label>
                <input
                    type="tel"
                    id="telephone"
                    name="telephone"
                    class="auth-input<?= $telephoneError !== null ? ' auth-input--error' : '' ?>"
                    value="<?= $esc($old['telephone'] ?? '') ?>"
                    placeholder="Ex : 77 123 45 67"
                    maxlength="20"
                    autocomplete="tel"
                    required
                    <?= $telephoneError !== null ? 'aria-invalid="true" aria-describedby="telephone-error"' : '' ?>
                >
                <?php if ($telephoneError !== null): ?>
                    <p class="auth-field-error" id="telephone-error"><?= $telephoneError ?></p>
                <?php endif; ?>
                <p class="auth-hint">Numéro local ou international, avec ou sans indicatif.</p>
            </div>

            <div class="auth-field-row">
                <div class="auth-field">
                    <label for="password">Mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="auth-input<?= $passwordError !== null ? ' auth-input--error' : '' ?>"
                        placeholder="••••••••"
                        minlength="8"
                        autocomplete="new-password"
                        required
                        <?= $passwordError !== null ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
                    >
                    <?php if ($passwordError !== null): ?>
                        <p class="auth-field-error" id="password-error"><?= $passwordError ?></p>
                    <?php else: ?>
                        <p class="auth-hint">8 caractères minimum.</p>
                    <?php endif; ?>
                </div>

                <div class="auth-field">
                    <label for="password_confirmation">Confirmation du mot de passe</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="auth-input<?= $passwordError !== null ? ' auth-input--error' : '' ?>"
                        placeholder="••••••••"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>
            </div>

            <div class="auth-field">
                <div class="auth-checkbox">
                    <input type="checkbox" id="terms" name="terms" value="1" required>
                    <label for="terms">
                        J'accepte les <a href="<?= base_path('annonces') ?>">conditions d'utilisation</a>
                        et la <a href="<?= base_path('annonces') ?>">politique de confidentialité</a>
                        de PetitesAnnonces.sn.
                    </label>
                </div>
                <?php if ($termsError !== null): ?>
                    <p class="auth-field-error"><?= $termsError ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="auth-btn">
                Créer mon compte
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </form>

        <p class="auth-alt">
            Déjà un compte ?
            <a href="<?= base_path('auth/login') ?>">Se connecter</a>
        </p>

        <p class="auth-legal">
            Vos informations restent confidentielles et ne sont jamais partagées
            avec des tiers sans votre accord.
        </p>
    </section>
</main>

<footer class="auth-footer">
    <p>© 2026 PetitesAnnonces.sn — Tous droits réservés</p>
    <p><a href="<?= base_path('/') ?>">Retour à l'accueil</a></p>
</footer>

</body>
</html>
