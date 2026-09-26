<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Router;
use App\Core\Session;

// ============================================
// FRONT CONTROLLER
// ============================================
// Point d'entrée unique de l'application.
// Toutes les requêtes HTTP sont routées ici
// grâce au fichier .htaccess à la racine.
// ============================================

// Constantes de chemins
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Autoloader de Composer :
//   - classes PSR-4 (app/ et core/) ;
//   - fonctions utilitaires globales de core/helpers.php déclarées
//     dans la clé « files » : config(), base_path(), url(), asset().
require ROOT_PATH . '/vendor/autoload.php';

// Chargement des variables d'environnement (.env)
Env::load(ROOT_PATH . '/.env');

// Démarrage de la session (nécessaire pour l'authentification)
Session::start();

// ============================================
// ROUTEUR
// ============================================

// Instanciation du routeur
$router = new Router();

// Chargement des routes déclarées dans config/routes.php
// Chaque entrée : ['method' => ..., 'path' => ..., 'handler' => [...], 'middleware' => [...]]
$routes = require CONFIG_PATH . '/routes.php';

foreach ($routes as $route) {
    $router->add(
        (string) $route['method'],
        (string) $route['path'],
        $route['handler'],
        (array) ($route['middleware'] ?? [])
    );
}

// ============================================
// RÉCUPÉRATION DE L'URI DEMANDÉE
// ============================================
// L'application est accessible via http://localhost/petites_annonces/
// Le préfixe /petites_annonces doit être retiré pour obtenir
// le chemin applicatif réel.
//
// Exemples :
//   /petites_annonces/           → /
//   /petites_annonces/annonces   → /annonces
//   /petites_annonces/annonces/12 → /annonces/12
// ============================================

// Récupère le chemin de l'URL (sans la query string)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Retire le préfixe du projet si présent (valeur issue de config/app.php)
$basePath = (string) config('app.base_path', '');

if ($basePath !== '' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

// Normalise : une chaîne vide devient '/'
$uri = $uri === '' ? '/' : $uri;

// Récupère la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Lance le routage
$router->dispatch($uri, $method);