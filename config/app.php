<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Configuration générale de l'application.
 *
 * Toutes les valeurs proviennent du fichier .env (via App\Core\Env),
 * avec des valeurs par défaut sûres.
 *
 * @return array<string, mixed>
 */

// Chemin de base normalisé : toujours « /quelquechose » ou « / ».
$rawBasePath = trim(Env::string('APP_BASE_PATH', '/petites_annonces'), '/');
$basePath = $rawBasePath === '' ? '' : '/' . $rawBasePath;

return [
    // Identité de l'application
    'name'  => Env::string('APP_NAME', 'PetitesAnnonces.sn'),
    'env'   => Env::string('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'timezone' => Env::string('APP_TIMEZONE', 'Africa/Dakar'),

    // URL absolue racine (sans slash final) : http://localhost/petites_annonces
    'url' => rtrim(Env::string('APP_URL', 'http://localhost/petites_annonces'), '/'),

    // Préfixe public de l'application (utilisé pour construire les liens)
    'base_path' => $basePath,

    // Session
    'session' => [
        'lifetime' => Env::int('SESSION_LIFETIME', 7200),
    ],

    // Chemins de stockage (hors racine web)
    'paths' => [
        'storage' => ROOT_PATH . '/storage',
        'logs'    => ROOT_PATH . '/storage/logs',
        'uploads' => ROOT_PATH . '/storage/uploads',
    ],
];
