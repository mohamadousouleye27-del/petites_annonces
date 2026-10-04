<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\MembreController;
use App\Controllers\ModerateurController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\RoleMiddleware;

/**
 * Tableau des routes de l'application.
 *
 * Chaque route est un tableau associatif :
 *   [
 *       'method'     => 'GET' | 'POST' | 'PUT' | 'DELETE'...,
 *       'path'       => '/chemin/{param}',
 *       'handler'    => [ClasseControleur::class, 'methode'],
 *       'middleware' => [Middleware::class, ...],   // facultatif
 *   ]
 *
 * Un middleware peut être :
 *   - le nom d'une classe           → MaClasseMiddleware::class
 *   - un tableau [classe, ...args]  → [RoleMiddleware::class, 'admin']
 *
 * Le middleware de rôle est prêt à l'emploi pour les routes
 * d'administration :
 *   use App\Middleware\RoleMiddleware;
 *
 * @return array<int, array<string, mixed>>
 */

return [
    // ============================================
    // Accueil (public)
    // ============================================
    [
        'method'  => 'GET',
        'path'    => '/',
        'handler' => [HomeController::class, 'index'],
    ],

    // ============================================
    // Authentification — réservée aux visiteurs
    // (un utilisateur connecté est renvoyé vers l'accueil)
    // ============================================
    [
        'method'     => 'GET',
        'path'       => '/auth/login',
        'handler'    => [AuthController::class, 'login'],
        'middleware' => [GuestMiddleware::class],
    ],
    [
        'method'     => 'POST',
        'path'       => '/auth/login',
        'handler'    => [AuthController::class, 'authenticate'],
        'middleware' => [GuestMiddleware::class],
    ],
    [
        'method'     => 'GET',
        'path'       => '/auth/register',
        'handler'    => [AuthController::class, 'register'],
        'middleware' => [GuestMiddleware::class],
    ],
    [
        'method'     => 'POST',
        'path'       => '/auth/register',
        'handler'    => [AuthController::class, 'store'],
        'middleware' => [GuestMiddleware::class],
    ],

    // ============================================
    // Déconnexion — réservée aux utilisateurs connectés
    // ============================================
    [
        'method'     => 'POST',
        'path'       => '/auth/logout',
        'handler'    => [AuthController::class, 'logout'],
        'middleware' => [AuthMiddleware::class],
    ],

    // ============================================
    // Espace Membre — rôle requis : member
    // ============================================
    // Cloisonnement STRICT : ces routes n'acceptent que le rôle « member ».
    //   - visiteur anonyme               → 302 vers /auth/login (AuthMiddleware)
    //   - connecté avec un autre rôle    → 403 (RoleMiddleware)
    // Aucune hiérarchie de rôles : admin et moderateur ne sont PAS membres.
    [
        'method'     => 'GET',
        'path'       => '/membre',
        'handler'    => [MembreController::class, 'dashboard'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'member']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/membre/annonces',
        'handler'    => [MembreController::class, 'annonces'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'member']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/membre/favoris',
        'handler'    => [MembreController::class, 'favoris'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'member']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/membre/messages',
        'handler'    => [MembreController::class, 'messages'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'member']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/membre/profil',
        'handler'    => [MembreController::class, 'profil'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'member']],
    ],

    // ============================================
    // Espace Modérateur — rôle requis : moderateur
    // ============================================
    // Cloisonnement STRICT : ces routes n'acceptent que le rôle « moderateur ».
    //   - visiteur anonyme            → 302 vers /auth/login (AuthMiddleware)
    //   - connecté avec un autre rôle → 403 (RoleMiddleware)
    // Aucune hiérarchie : member et admin ne sont PAS modérateurs.
    [
        'method'     => 'GET',
        'path'       => '/moderateur',
        'handler'    => [ModerateurController::class, 'dashboard'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'moderateur']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/moderateur/signalements',
        'handler'    => [ModerateurController::class, 'signalements'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'moderateur']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/moderateur/annonces',
        'handler'    => [ModerateurController::class, 'annonces'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'moderateur']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/moderateur/utilisateurs',
        'handler'    => [ModerateurController::class, 'utilisateurs'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'moderateur']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/moderateur/journal',
        'handler'    => [ModerateurController::class, 'journal'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'moderateur']],
    ],
    [
        // Profil du modérateur CONNECTÉ (lecture seule, GET uniquement).
        // L'identité affichée provient de la clé de session `user_id` :
        // aucun paramètre d'URL ne permet de consulter un autre profil.
        'method'     => 'GET',
        'path'       => '/moderateur/profil',
        'handler'    => [ModerateurController::class, 'profil'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'moderateur']],
    ],

    // ============================================
    // Espace Administrateur — rôle requis : admin
    // ============================================
    // Cloisonnement STRICT : ces routes n'acceptent que le rôle « admin ».
    //   - visiteur anonyme            → 302 vers /auth/login (AuthMiddleware)
    //   - connecté avec un autre rôle → 403 (RoleMiddleware)
    // Aucune hiérarchie : member et moderateur ne sont PAS administrateurs.
    [
        'method'     => 'GET',
        'path'       => '/admin',
        'handler'    => [AdminController::class, 'dashboard'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/admin/utilisateurs',
        'handler'    => [AdminController::class, 'utilisateurs'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/admin/annonces',
        'handler'    => [AdminController::class, 'annonces'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/admin/categories',
        'handler'    => [AdminController::class, 'categories'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/admin/villes',
        'handler'    => [AdminController::class, 'villes'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/admin/signalements',
        'handler'    => [AdminController::class, 'signalements'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        'method'     => 'GET',
        'path'       => '/admin/journal',
        'handler'    => [AdminController::class, 'journal'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],
    [
        // Profil de l'administrateur CONNECTÉ (lecture seule, GET uniquement).
        // L'identité affichée provient de la clé de session `user_id` :
        // aucun paramètre d'URL ne permet de consulter un autre profil.
        'method'     => 'GET',
        'path'       => '/admin/profil',
        'handler'    => [AdminController::class, 'profil'],
        'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    ],

    // ============================================
    // ROUTES À VENIR (contrôleurs pas encore implémentés)
    // ============================================
    // Les trois espaces connectés (/membre, /moderateur, /admin) sont
    // désormais déclarés ci-dessus. Les blocs commentés restent indicatifs
    // pour le parcours public (annonces, favoris, messages, profil).
    // Les contrôleurs ci-dessous n'existent pas encore : déclarer
    // leurs routes provoquerait une erreur 500 (classe introuvable).
    // Chaque bloc sera décommenté dès que le contrôleur correspondant
    // sera implémenté ; les middlewares nécessaires sont déjà indiqués.
    //
    // --- Annonces (public) ---
    // ['method' => 'GET', 'path' => '/annonces',      'handler' => [AnnonceController::class, 'index']],
    // ['method' => 'GET', 'path' => '/annonces/{id}', 'handler' => [AnnonceController::class, 'show']],
    //
    // --- Favoris (connecté) ---
    // [
    //     'method'     => 'GET',
    //     'path'       => '/favoris',
    //     'handler'    => [FavoriController::class, 'index'],
    //     'middleware' => [AuthMiddleware::class],
    // ],
    //
    // --- Messages (connecté) ---
    // [
    //     'method'     => 'GET',
    //     'path'       => '/messages',
    //     'handler'    => [MessageController::class, 'index'],
    //     'middleware' => [AuthMiddleware::class],
    // ],
    //
    // --- Profil (connecté) ---
    // [
    //     'method'     => 'GET',
    //     'path'       => '/profil',
    //     'handler'    => [ProfilController::class, 'index'],
    //     'middleware' => [AuthMiddleware::class],
    // ],
    //
    // --- Administration (connecté + rôle admin) ---
    // [
    //     'method'     => 'GET',
    //     'path'       => '/admin',
    //     'handler'    => [AdminController::class, 'index'],
    //     'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
    // ],
];
