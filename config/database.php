<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Configuration de la base de données.
 *
 * Ce fichier ne contient QUE des valeurs de configuration : aucune
 * logique de connexion (celle-ci vit désormais dans App\Core\Database).
 *
 * Les valeurs sont lues depuis le fichier .env via App\Core\Env, avec
 * des valeurs par défaut adaptées à un environnement XAMPP local.
 *
 * @return array<string, mixed>
 */

return [
    'driver'   => Env::string('DB_DRIVER', 'mysql'),
    'host'     => Env::string('DB_HOST', 'localhost'),
    'port'     => Env::int('DB_PORT', 3306),
    'database' => Env::string('DB_DATABASE', 'petites_annonces'),
    'username' => Env::string('DB_USERNAME', 'root'),
    'password' => Env::string('DB_PASSWORD', ''),
    'charset'  => Env::string('DB_CHARSET', 'utf8mb4'),
];
