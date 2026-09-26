<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

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
    // ROUTES À VENIR (contrôleurs pas encore implémentés)
    // ============================================
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
