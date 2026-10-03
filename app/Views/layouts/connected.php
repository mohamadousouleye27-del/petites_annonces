<?php
/**
 * Layout commun des espaces connectés (membre, modérateur, administrateur).
 *
 * Contrat de données — toutes les clés sont facultatives et disposent d'un
 * défaut sûr :
 *
 *   $pageTitle     string  Titre de la page (bandeau + balise <title>)
 *   $pageSubtitle  string  Sous-titre du bandeau (optionnel)
 *   $content       string  HTML déjà rendu, fourni par Controller::viewWithLayout()
 *   $menu          array   Navigation principale, chaque entrée :
 *                          ['label' => string, 'href' => string,
 *                           'icone' => string, 'actif' => bool, 'exact' => bool]
 *   $spaceLabel    string  Libellé de l'espace (« Espace Membre »...)
 *   $roleLabel     string  Libellé français du rôle (« Membre », « Modérateur »,
 *                          « Administrateur »). Surchargé par App\Core\Auth::label()
 *                          dès que le rôle de la session est connu : un seul
 *                          référentiel de libellés.
 *   $currentUser   array   ['prenom', 'nom', 'email', 'role', 'avatar'] — données
 *                          fournies par le contrôleur. TOUTES sont facultatives :
 *                          chaque valeur manquante est complétée par le layout
 *                          lui-même (table `users` via User::trouverParId(), puis
 *                          session). L'identité vient EXCLUSIVEMENT de la session
 *                          authentifiée (`user_id`), jamais de l'URL ni du client.
 *   $userLinks     array   Liens du menu utilisateur : ['label', 'href', 'icone']
 *
 * ÉTAPE 7.4 — le layout est RÉELLEMENT DYNAMIQUE et partagé par les trois
 * espaces (member / moderateur / admin), sans duplication :
 *   - nom complet robuste (prénom seul, nom seul, ou « Utilisateur ») ;
 *   - libellé de rôle issu de Auth::label() ;
 *   - avatar si exploitable, sinon initiales (dashAvatar()) ;
 *   - navigation par rôle : celle du contrôleur si elle est fournie, sinon
 *     celle déduite du rôle (dashMenuParRole()) ;
 *   - lien actif calculé par dashIsActive() sur l'URL courante.
 *
 * PERFORMANCE : au plus UNE lecture de l'utilisateur connecté par requête HTTP.
 * La ligne `users` n'est lue que si le contrôleur n'a pas déjà transmis
 * prénom + nom + email (cas de l'espace Membre, qui met déjà
 * User::trouverParId() en cache), et la lecture est mémorisée par variable
 * statique. Aucun N+1.
 *
 * CLOISONNEMENT : ce layout ne contrôle AUCUN rôle et ne décide d'aucun accès.
 * Le rôle technique (« member », « moderateur », « admin ») n'y apparaît que
 * comme valeur affichée (attributs data-role / title), jamais dans une
 * condition. L'autorisation reste exclusivement côté serveur, via
 * AuthMiddleware et RoleMiddleware.
 *
 * RESPONSIVE SANS JAVASCRIPT : la sidebar repose sur une case à cocher et un
 * <label> de recouvrement (CSS uniquement), le menu utilisateur sur un
 * élément <details> natif. public/assets/js/dashboard.js n'apporte qu'un
 * confort (Échap, clic extérieur, attributs ARIA) : la navigation et la
 * déconnexion fonctionnent même sans JavaScript.
 *
 * Aucune donnée utilisateur n'est affichée sans échappement HTML.
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\User;

// Helpers d'affichage propres aux espaces connectés.
// (Également chargés en amont par Controller::viewWithLayout(), la vue
//  étant rendue avant le layout : ce require_once est donc un filet de
//  sécurité idempotent si le layout est inclus directement.)
require_once __DIR__ . '/partials/connected-helpers.php';

// --- Échappement systématique des valeurs affichées ----------------------
$esc = static fn (mixed $valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// --- Normalisation défensive : aucun warning si une donnée est absente ---
$content = isset($content) && is_string($content) ? $content : '';

$pageTitle = isset($pageTitle) && is_string($pageTitle) && trim($pageTitle) !== ''
    ? trim($pageTitle)
    : 'Espace connecté';

$pageSubtitle = isset($pageSubtitle) && is_string($pageSubtitle) ? trim($pageSubtitle) : '';

$spaceLabel = isset($spaceLabel) && is_string($spaceLabel) && trim($spaceLabel) !== ''
    ? trim($spaceLabel)
    : 'Espace connecté';

$roleLabel = isset($roleLabel) && is_string($roleLabel) && trim($roleLabel) !== ''
    ? trim($roleLabel)
    : 'Utilisateur';

$menu = isset($menu) && is_array($menu) ? $menu : [];
$userLinks = isset($userLinks) && is_array($userLinks) ? $userLinks : [];
$currentUser = isset($currentUser) && is_array($currentUser) ? $currentUser : [];

// ===========================================================================
// ÉTAPE 7.4 — IDENTITÉ RÉELLE DE L'UTILISATEUR CONNECTÉ
// ===========================================================================
//
// SOURCE UNIQUE D'IDENTITÉ : la session authentifiée (`user_id`).
// Jamais GET, POST, URL ni cookie : le contrôleur qui rend cette page a
// déjà été validé par AuthMiddleware + RoleMiddleware.
//
// 1. Identifiant : session uniquement.
// 2. Ligne `users` : User::trouverParId(), appelé AU PLUS UNE FOIS par
//    requête HTTP (mémorisation statique ci-dessous). Le modèle est déjà
//    mis en cache par MembreController sur l'espace Membre : sur cet espace,
//    les données fournies par le contrôleur sont donc réutilisées telles
//    quelles et AUCUNE requête supplémentaire n'est émise.
// 3. Repli : session (`user_prenom`, `user_role`) si le compte est introuvable.
// 4. Aucune donnée sensible n'est lue : ni password_hash, ni token, ni statut
//    d'authentification. Seuls prenom, nom, email, role et avatar sont repris.

$sessionUserId = Session::get('user_id');
$sessionUserId = is_string($sessionUserId) ? trim($sessionUserId) : '';

/**
 * Valeur textuelle nettoyée, quelle que soit sa provenance.
 */
$texte = static function (mixed $valeur): string {
    return is_string($valeur) ? trim($valeur) : '';
};

/**
 * Ligne `users` de l'utilisateur connecté, récupérée UNE SEULE FOIS par
 * requête HTTP (variable statique) : aucun N+1, aucun doublon de requête
 * même si le layout est inclus plusieurs fois.
 *
 * @param string $userId UUID issu de la session
 * @return array<string, mixed>|null
 */
$chargerUtilisateur = static function (string $userId): ?array {
    /** @var array<string, mixed>|null $cache */
    static $cache = null;
    static $charge = false;

    if ($charge) {
        return $cache;
    }

    $charge = true;

    try {
        $cache = (new User())->trouverParId($userId);
    } catch (\Throwable $e) {
        // Base indisponible : le layout reste affichable (repli session)
        error_log('Layout connecté : lecture de l\'utilisateur impossible (code ' . $e->getCode() . ').');

        $cache = null;
    }

    return $cache;
};

// --- Besoin réel d'une lecture en base ? -----------------------------------
// Aucune requête n'est émise si le contrôleur a déjà transmis prénom, nom et
// email : c'est le cas de l'espace Membre, dont le contrôleur met déjà
// User::trouverParId() en cache. Le layout n'y émet donc AUCUNE requête
// supplémentaire. Le champ avatar n'est PAS un motif de lecture : il est
// absent de tous les comptes (aucun envoi d'image n'existe) et sert
// uniquement de repli via dashAvatar().
//
// Au maximum : une seule lecture de l'utilisateur connecté par requête HTTP
// sur les espaces Modérateur et Administrateur, grâce à la mémorisation
// statique ci-dessus.
$besoinBase = $sessionUserId !== '' && (
    $texte($currentUser['prenom'] ?? null) === ''
    || $texte($currentUser['nom'] ?? null) === ''
    || $texte($currentUser['email'] ?? null) === ''
);

$ligneUtilisateur = $besoinBase ? $chargerUtilisateur($sessionUserId) : null;
$ligneUtilisateur = is_array($ligneUtilisateur) ? $ligneUtilisateur : [];

// --- Rôle : la session authentifiée fait foi, la base en complément -------
$userRole = $texte(Session::get('user_role'));

if ($userRole === '') {
    $userRole = $texte($currentUser['role'] ?? null);
}

if ($userRole === '') {
    $userRole = $texte($ligneUtilisateur['role'] ?? null);
}

// --- Libellé de rôle : référentiel unique App\Core\Auth::label() ----------
$roleLabelSession = Auth::label($userRole);

if ($roleLabelSession !== '') {
    $roleLabel = $roleLabelSession;
}

// --- Prénom / nom / email : contrôleur, puis base, puis session -----------
$userPrenom = $texte($currentUser['prenom'] ?? null);

if ($userPrenom === '') {
    $userPrenom = $texte($ligneUtilisateur['prenom'] ?? null);
}

if ($userPrenom === '') {
    $userPrenom = $texte(Session::get('user_prenom'));
}

$userNom = $texte($currentUser['nom'] ?? null);

if ($userNom === '') {
    $userNom = $texte($ligneUtilisateur['nom'] ?? null);
}

$userEmail = $texte($currentUser['email'] ?? null);

if ($userEmail === '') {
    $userEmail = $texte($ligneUtilisateur['email'] ?? null);
}

// --- Avatar : valeur brute de la base, exploitée par dashAvatar() ----------
$userAvatar = $currentUser['avatar'] ?? ($ligneUtilisateur['avatar'] ?? null);

// --- Nom complet : jamais d'espace inutile, jamais de valeur vide affichée --
$userNomComplet = trim($userPrenom . ' ' . $userNom);
$userNomComplet = $userNomComplet !== '' ? $userNomComplet : 'Utilisateur';

// --- Navigation : celle du contrôleur, sinon celle du rôle (filet) ---------
if ($menu === []) {
    $menu = dashMenuParRole($userRole);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esc($pageTitle) ?> — PetitesAnnonces.sn</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Espace connecté PetitesAnnonces.sn.">

    <!-- TailwindCSS CDN (mêmes jetons de design que la page d'accueil) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        stone: {
                            50: '#fafaf9', 100: '#f5f5f4', 200: '#e7e5e4', 300: '#d6d3d1',
                            400: '#a8a29e', 500: '#78716c', 600: '#57534e', 700: '#44403c',
                            800: '#292524', 900: '#1c1917',
                        },
                        teal: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4',
                            400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e',
                            800: '#115e59', 900: '#134e4a',
                        },
                        amber: {
                            50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d',
                            400: '#fbbf24', 500: '#f59e0b', 600: '#d97706',
                        },
                    },
                },
            },
        };
    </script>

    <!-- Styles de l'espace connecté (classes préfixées « dash- ») -->
    <link rel="stylesheet" href="<?= asset('assets/css/dashboard.css') ?>">
</head>
<body class="dash-page">

<?php /* Case à cocher pilotant la sidebar en mobile (CSS uniquement) */ ?>
<input type="checkbox" id="dash-sidebar-toggle" class="dash-sidebar-toggle-input">

<div class="dash-layout">

    <!-- ============================================
         SIDEBAR
         ============================================ -->
    <aside class="dash-sidebar" id="dash-sidebar" aria-label="Navigation de l'espace connecté">
        <a href="<?= base_path('/') ?>" class="dash-brand">
            <span class="dash-brand-mark" aria-hidden="true">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </span>
            <span class="dash-brand-text">
                Petites<span class="dash-brand-amber">Annonces</span><span class="dash-brand-teal">.sn</span>
            </span>
        </a>

        <p class="dash-space-label"><?= $esc($spaceLabel) ?></p>

        <nav class="dash-nav">
            <?php foreach ($menu as $item): ?>
                <?php
                // Entrée invalide (non tableau, ou sans lien) : ignorée
                if (!is_array($item)) {
                    continue;
                }

                $itemHref = isset($item['href']) && is_string($item['href']) ? trim($item['href']) : '';

                if ($itemHref === '') {
                    continue;
                }

                $itemLabel = isset($item['label']) && is_string($item['label']) ? $item['label'] : '';
                $itemIcone = isset($item['icone']) && is_string($item['icone']) ? $item['icone'] : 'neutre';
                $itemExact = !empty($item['exact']);

                // « actif » explicite prioritaire ; sinon détection par comparaison d'URL
                $itemActif = array_key_exists('actif', $item)
                    ? (bool) $item['actif']
                    : dashIsActive($itemHref, $itemExact);
                ?>
                <a
                    class="dash-nav-link<?= $itemActif ? ' is-active' : '' ?>"
                    href="<?= $esc($itemHref) ?>"
                    <?= $itemActif ? 'aria-current="page"' : '' ?>
                >
                    <?= dashIcon($itemIcone) ?>
                    <span class="dash-nav-label"><?= $esc($itemLabel) ?></span>
                </a>
            <?php endforeach; ?>

            <?php if ($menu === []): ?>
                <p class="dash-nav-empty">Navigation à venir.</p>
            <?php endif; ?>
        </nav>

        <div class="dash-sidebar-foot">
            <a href="<?= base_path('/') ?>" class="dash-nav-link dash-nav-link--ghost">
                <?= dashIcon('fleche') ?>
                <span class="dash-nav-label">Retour au site</span>
            </a>
        </div>
    </aside>

    <?php /* Recouvrement : ferme la sidebar au clic, sans JavaScript */ ?>
    <label class="dash-overlay" for="dash-sidebar-toggle" aria-hidden="true"></label>

    <!-- ============================================
         ZONE PRINCIPALE
         ============================================ -->
    <div class="dash-main">

        <!-- ============================================
             TOPBAR
             ============================================ -->
        <header class="dash-topbar">
            <?php /* Déclencheur de la sidebar en mobile (label → CSS uniquement) */ ?>
            <label
                class="dash-burger"
                id="dash-burger"
                for="dash-sidebar-toggle"
                role="button"
                tabindex="0"
                aria-expanded="false"
                aria-controls="dash-sidebar"
                aria-label="Afficher ou masquer la navigation"
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </label>

            <div class="dash-topbar-titles">
                <h1 class="dash-topbar-title"><?= $esc($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ''): ?>
                    <p class="dash-topbar-subtitle"><?= $esc($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>

            <div class="dash-topbar-actions">
                <?php
                /*
                 * Badge de rôle : valeur PUREMENT AFFICHÉE.
                 * data-role contient le rôle technique tel quel
                 * (member | moderateur | admin) ; aucune décision d'accès
                 * n'est prise ici ni côté client.
                 */
                ?>
                <span
                    class="dash-role-badge"
                    data-role="<?= $esc($userRole) ?>"
                    title="Rôle du compte : <?= $esc($userRole !== '' ? $userRole : 'inconnu') ?>"
                >
                    <span class="dash-role-dot" aria-hidden="true"></span>
                    <?= $esc($roleLabel) ?>
                </span>

                <?php /* Menu utilisateur : <details> natif → accessible sans JavaScript */ ?>
                <details class="dash-user-menu" id="dash-user-menu">
                    <summary class="dash-user-btn">
                        <?= dashAvatar($userAvatar, $userPrenom, $userNom) ?>
                        <span class="dash-user-name"><?= $esc($userNomComplet) ?></span>
                        <?= dashIcon('chevron') ?>
                    </summary>

                    <div class="dash-user-dropdown">
                        <div class="dash-user-identity">
                            <p class="dash-user-fullname"><?= $esc($userNomComplet) ?></p>
                            <?php if ($userEmail !== ''): ?>
                                <p class="dash-user-email"><?= $esc($userEmail) ?></p>
                            <?php endif; ?>
                            <p class="dash-user-role">
                                <?= $esc($roleLabel) ?>
                                <span class="dash-user-role-code"><?= $esc($userRole !== '' ? $userRole : '—') ?></span>
                            </p>
                        </div>

                        <?php foreach ($userLinks as $lien): ?>
                            <?php
                            if (!is_array($lien)) {
                                continue;
                            }

                            $lienHref = isset($lien['href']) && is_string($lien['href']) ? trim($lien['href']) : '';

                            if ($lienHref === '') {
                                continue;
                            }

                            $lienLabel = isset($lien['label']) && is_string($lien['label']) ? $lien['label'] : '';
                            $lienIcone = isset($lien['icone']) && is_string($lien['icone']) ? $lien['icone'] : 'neutre';
                            ?>
                            <a class="dash-user-link" href="<?= $esc($lienHref) ?>">
                                <?= dashIcon($lienIcone) ?>
                                <span><?= $esc($lienLabel) ?></span>
                            </a>
                        <?php endforeach; ?>

                        <?php
                        /*
                         * Déconnexion : réutilise l'endpoint sécurisé existant
                         * POST /auth/logout (vérification CSRF + destruction de
                         * session côté serveur). Aucune logique de session n'est
                         * dupliquée dans le layout.
                         */
                        ?>
                        <form method="POST" action="<?= base_path('auth/logout') ?>" class="dash-logout-form">
                            <?= Csrf::field() ?>
                            <button type="submit" class="dash-logout-btn">
                                <?= dashIcon('deconnexion') ?>
                                <span>Se déconnecter</span>
                            </button>
                        </form>
                    </div>
                </details>

            </div>
        </header>

        <!-- ============================================
             CONTENU DE LA PAGE
             ============================================ -->
        <?php
        /*
         * $content est le HTML déjà rendu par une vue de app/Views/ : il est
         * volontairement affiché sans échappement (échapper ici casserait le
         * markup). Les vues concernées échappent elles-mêmes chaque donnée
         * utilisateur via $esc (htmlspecialchars + ENT_QUOTES).
         */
        ?>
        <main class="dash-content" id="dash-content">
            <?= $content ?>
        </main>

        <!-- ============================================
             PIED DE PAGE
             ============================================ -->
        <footer class="dash-footer">
            <p>© 2026 PetitesAnnonces.sn — Tous droits réservés</p>
            <p><a href="<?= base_path('/') ?>">Retour à l'accueil</a></p>
        </footer>
    </div>


</div>

<script src="<?= asset('assets/js/dashboard.js') ?>"></script>
</body>
</html>
