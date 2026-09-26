<?php
/**
 * Vue : formulaire de connexion (GET /auth/login).
 *
 * Données fournies par App\Controllers\AuthController::renderLoginForm() :
 *   $title        : titre de la page
 *   $old          : valeurs non sensibles saisies (email uniquement)
 *   $errors       : erreurs de validation, indexées par champ
 *   $generalError : message d'erreur non lié à un champ (identifiants
 *                   invalides, erreur technique) ou null
 *   $csrfError    : erreur de protection CSRF (ou null)
 *
 * Aucune valeur utilisateur n'est affichée sans échappement HTML
 * (htmlspecialchars + ENT_QUOTES). Le mot de passe n'est jamais réaffiché.
 */

use App\Core\Csrf;

// Échappement systématique des valeurs affichées
$esc = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Récupère le message d'erreur (déjà échappé) d'un champ, ou null
$fieldError = static function (string $field) use ($errors, $esc): ?string {
    $message = $errors[$field] ?? null;

    return $message !== null ? $esc($message) : null;
};

$emailError    = $fieldError('email');
$passwordError = $fieldError('password');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esc($title ?? 'Connexion') ?></title>
    <meta name="description" content="Connectez-vous à votre compte PetitesAnnonces.sn pour gérer vos annonces, vos favoris et vos messages.">

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
            <h2 class="auth-brand-title">Bon retour parmi nous</h2>
            <p class="auth-brand-desc">
                Retrouvez vos annonces, vos favoris et vos messages
                en quelques secondes, partout au Sénégal.
            </p>

            <ul class="auth-brand-list">
                <li>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Gérez vos annonces en toute simplicité
                </li>
                <li>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Échangez directement avec les acheteurs
                </li>
                <li>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Suivez vos favoris et vos conversations
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
            <a href="<?= base_path('auth/register') ?>" class="auth-head-link">Créer un compte</a>
        </div>

        <h1 class="auth-title">Se connecter</h1>
        <p class="auth-subtitle">
            Accédez à votre espace pour publier, suivre vos annonces et discuter
            avec les autres membres.
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

        <!-- Message général : identifiants invalides ou erreur technique -->
        <?php if ($generalError !== null): ?>
            <div class="auth-alert auth-alert--error" role="alert">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.34 3.94l-8.14 14.1A1.5 1.5 0 003.5 20.25h17A1.5 1.5 0 0021.8 18.04L13.66 3.94a1.5 1.5 0 00-2.6 0z"/>
                </svg>
                <span><?= $esc($generalError) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_path('auth/login') ?>" class="auth-form" novalidate>
            <?= Csrf::field() ?>

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
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="auth-input<?= $passwordError !== null ? ' auth-input--error' : '' ?>"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                    <?= $passwordError !== null ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>
                >
                <?php if ($passwordError !== null): ?>
                    <p class="auth-field-error" id="password-error"><?= $passwordError ?></p>
                <?php endif; ?>
            </div>
            <button type="submit" class="auth-btn">
                Se connecter
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </form>

        <p class="auth-alt">
            Pas encore de compte ?
            <a href="<?= base_path('auth/register') ?>">Créer un compte</a>
        </p>

        <p class="auth-legal">
            Vos identifiants restent confidentiels et ne sont jamais partagés
            avec des tiers.
        </p>
    </section>
</main>

<footer class="auth-footer">
    <p>© 2026 PetitesAnnonces.sn — Tous droits réservés</p>
    <p><a href="<?= base_path('/') ?>">Retour à l'accueil</a></p>
</footer>

</body>
</html>
