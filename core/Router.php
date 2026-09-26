<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Routeur MVC
 *
 * Enregistre les routes de l'application et les fait correspondre
 * aux requêtes HTTP entrantes. Chaque route associe une méthode HTTP
 * et un chemin à un contrôleur et une action.
 *
 * Exemple :
 *   $router->add('GET', '/annonces', [AnnonceController::class, 'index']);
 *   $router->add('GET', '/annonces/{id}', [AnnonceController::class, 'show']);
 *
 * Le routeur ne gère PAS la récupération des données GET/POST/JSON :
 * cette responsabilité appartient à la classe Controller.
 *
 * @package App\Core
 */
class Router
{
    /**
     * Tableau des routes enregistrées.
     *
     * Structure :
     *   [
     *     'GET' => [
     *       '/annonces' => ['controller' => ..., 'action' => ...],
     *       '/annonces/{id}' => ['controller' => ..., 'action' => ...],
     *     ],
     *     'POST' => [...],
     *   ]
     *
     * @var array<string, array<string, array{controller: string, action: string, middleware: array<int, mixed>}>>
     */
    private array $routes = [];

    /**
     * Enregistre une nouvelle route.
     *
     * @param string $method Méthode HTTP (GET, POST, PUT, DELETE, etc.)
     * @param string $path Chemin de la route (ex: '/annonces/{id}')
     * @param array{0: string, 1: string} $handler [NomClasseControleur, 'nomAction']
     * @param array<int, mixed> $middleware Middlewares exécutés avant l'action.
     *        Chaque entrée est soit un nom de classe, soit un tableau
     *        [Classe, argument1, ...] pour un middleware paramétré
     *        (ex: [RoleMiddleware::class, 'admin']).
     * @return void
     */
    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        // Normalise la méthode HTTP en majuscules
        $method = strtoupper($method);

        // Normalise le chemin : garantit un slash initial et pas de slash final
        $path = '/' . trim($path, '/');

        // Enregistre la route
        $this->routes[$method][$path] = [
            'controller' => $handler[0],
            'action'     => $handler[1],
            'middleware' => $middleware,
        ];
    }

    /**
     * Analyse la requête entrante et exécute la route correspondante.
     *
     * @param string $uri Chemin demandé (ex: '/annonces/12')
     * @param string $method Méthode HTTP (ex: 'GET')
     * @return void
     */
    public function dispatch(string $uri, string $method): void
    {
        // Normalise la méthode HTTP en majuscules
        $method = strtoupper($method);

        // Normalise l'URI : garantit un slash initial et pas de slash final
        $uri = '/' . trim($uri, '/');

        // Recherche une route correspondante pour la méthode HTTP demandée
        $match = $this->match($uri, $method);

        if ($match !== null) {
            // Route trouvée : instancie le contrôleur et appelle l'action
            try {
                $this->callHandler($match['handler'], $match['params']);
            } catch (RuntimeException $e) {
                // Erreur interne : renvoie une page 500 propre
                $this->internalServerError();
            }
            return;
        }

        // Aucune route pour cette méthode : vérifie si l'URL existe
        // avec une autre méthode HTTP (pour renvoyer un 405)
        $allowedMethods = [];
        foreach ($this->routes as $routeMethod => $routes) {
            foreach ($routes as $path => $handler) {
                if ($this->pathMatches($path, $uri)) {
                    $allowedMethods[] = $routeMethod;
                }
            }
        }

        if (!empty($allowedMethods)) {
            // L'URL existe mais la méthode HTTP n'est pas autorisée
            $this->methodNotAllowed($method, $allowedMethods);
            return;
        }

        // Aucune route ne correspond du tout
        $this->notFound();
    }

    /**
     * Recherche une route correspondant à l'URI et à la méthode HTTP.
     *
     * @param string $uri Chemin demandé normalisé
     * @param string $method Méthode HTTP normalisée
     * @return array{handler: array{controller: string, action: string}, params: array<string, string>}|null
     */
    private function match(string $uri, string $method): ?array
    {
        // Vérifie si des routes existent pour cette méthode HTTP
        if (!isset($this->routes[$method])) {
            return null;
        }

        // Parcourt toutes les routes de cette méthode
        foreach ($this->routes[$method] as $path => $handler) {
            // Extrait les paramètres dynamiques si le chemin correspond
            $params = $this->extractParams($path, $uri);

            if ($params !== null) {
                return [
                    'handler' => $handler,
                    'params'  => $params,
                ];
            }
        }

        return null;
    }

    /**
     * Vérifie si un chemin de route correspond à une URI donnée.
     *
     * @param string $path Chemin de la route (ex: '/annonces/{id}')
     * @param string $uri URI demandée (ex: '/annonces/12')
     * @return bool True si le chemin correspond, false sinon
     */
    private function pathMatches(string $path, string $uri): bool
    {
        return $this->extractParams($path, $uri) !== null;
    }

    /**
     * Extrait les paramètres dynamiques d'une URI par rapport à un chemin de route.
     *
     * Convertit le chemin de route en expression régulière :
     *   '/annonces/{id}' → '#^/annonces/(?P<id>[^/]+)$#'
     *
     * @param string $path Chemin de la route (ex: '/annonces/{id}')
     * @param string $uri URI demandée (ex: '/annonces/12')
     * @return array<string, string>|null Paramètres extraits ou null si pas de correspondance
     */
    private function extractParams(string $path, string $uri): ?array
    {
        // Convertit le chemin de route en expression régulière
        $regex = $this->convertToRegex($path);

        // Teste la correspondance
        if (preg_match($regex, $uri, $matches) === 1) {
            // Ne conserve que les paramètres nommés (pas les index numériques)
            $params = array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            return $params;
        }

        return null;
    }

    /**
     * Convertit un chemin de route en expression régulière.
     *
     * Les segments entre accolades deviennent des groupes nommés :
     *   '/annonces/{id}' → '#^/annonces/(?P<id>[^/]+)$#'
     *
     * @param string $path Chemin de la route
     * @return string Expression régulière correspondante
     */
    private function convertToRegex(string $path): string
    {
        // Échappe les caractères spéciaux de l'expression régulière
        // (hors accolades qui sont traitées ensuite)
        $pattern = preg_quote($path, '#');

        // Remplace les paramètres dynamiques {nom} par des groupes nommés
        // Exemple : '/annonces/{id}' → '/annonces/(?P<id>[^/]+)'
        $pattern = preg_replace_callback(
            '/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/',
            fn(array $m) => '(?P<' . $m[1] . '>[^/]+)',
            $pattern
        );

        // Encadre le motif avec les délimiteurs et les ancres
        return '#^' . $pattern . '$#';
    }

    /**
     * Exécute les middlewares puis appelle l'action du contrôleur.
     *
     * Les middlewares sont exécutés dans l'ordre déclaré. Si l'un d'eux
     * refuse la requête (redirection ou 403), il termine l'exécution et
     * l'action du contrôleur n'est jamais atteinte.
     *
     * @param array{controller: string, action: string, middleware: array<int, mixed>} $handler Contrôleur, action et middlewares
     * @param array<string, string> $params Paramètres dynamiques de la route
     * @return void
     */
    private function callHandler(array $handler, array $params): void
    {
        // 1. Middlewares (avant tout accès au contrôleur)
        $this->runMiddleware($handler['middleware'] ?? []);

        // 2. Contrôleur
        $controllerClass = $handler['controller'];
        $action = $handler['action'];

        // Vérifie que la classe du contrôleur existe
        if (!class_exists($controllerClass)) {
            $this->internalServerError();
        }

        // Instancie le contrôleur
        $controller = new $controllerClass();

        // Vérifie que l'action existe sur le contrôleur
        if (!method_exists($controller, $action)) {
            $this->internalServerError();
        }

        // Appelle l'action avec les paramètres dynamiques
        $controller->$action(...array_values($params));
    }

    /**
     * Exécute une chaîne de middlewares.
     *
     * Chaque entrée peut être :
     *   - une chaîne            : nom de classe instancié sans argument ;
     *   - un tableau            : [Classe, argument1, argument2...].
     *
     * Un middleware doit exposer une méthode publique handle().
     * Si handle() retourne normalement, le middleware suivant est
     * exécuté ; s'il interrompt la requête (exit/redirect), le
     * déroulement s'arrête et le contrôleur n'est pas appelé.
     *
     * @param array<int, mixed> $middleware Liste des middlewares à exécuter
     * @return void
     */
    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $definition) {
            $class = $definition;
            $arguments = [];

            // Middleware paramétré : [Classe::class, 'argument', ...]
            if (is_array($definition)) {
                $class = (string) ($definition[0] ?? '');
                $arguments = array_slice($definition, 1);
            }

            $class = is_string($class) ? $class : '';

            // Middleware inconnu ou mal déclaré : erreur serveur
            if ($class === '' || !class_exists($class)) {
                $this->internalServerError();
            }

            $instance = $arguments === [] ? new $class() : new $class(...$arguments);

            // Contrat minimal : une méthode handle() publique
            if (!method_exists($instance, 'handle')) {
                $this->internalServerError();
            }

            $instance->handle();
        }
    }

    /**
     * Affiche une erreur 404 Not Found.
     *
     * @return never
     */
    private function notFound(): never
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page introuvable</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        h1 { color: #e74c3c; font-size: 48px; margin-bottom: 10px; }
        p { color: #666; font-size: 18px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Page introuvable</p>
    <p><a href="' . base_path('/') . '">Retour à l\'accueil</a></p>
</body>
</html>';
        exit;
    }

    /**
     * Affiche une erreur 405 Method Not Allowed.
     *
     * @param string $method Méthode HTTP non autorisée
     * @return never
     */
    private function methodNotAllowed(string $method, array $allowedMethods): never
    {
        http_response_code(405);
        header('Content-Type: text/html; charset=utf-8');

        // En-tête Allow : méthodes réellement disponibles pour cette route
        header('Allow: ' . implode(', ', $allowedMethods));

        $method = htmlspecialchars($method, ENT_QUOTES, 'UTF-8');

        echo sprintf(
            '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>405 - Méthode non autorisée</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        h1 { color: #e67e22; font-size: 48px; margin-bottom: 10px; }
        p { color: #666; font-size: 18px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>405</h1>
    <p>Méthode non autorisée : %s</p>
    <p><a href="' . base_path('/') . '">Retour à l\'accueil</a></p>
</body>
</html>',
            $method
        );
        exit;
    }

    /**
     * Affiche une erreur 500 Internal Server Error.
     *
     * @return never
     */
    private function internalServerError(): never
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Erreur interne</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        h1 { color: #c0392b; font-size: 48px; margin-bottom: 10px; }
        p { color: #666; font-size: 18px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>500</h1>
    <p>Une erreur interne est survenue. Veuillez réessayer plus tard.</p>
    <p><a href="' . base_path('/') . '">Retour à l\'accueil</a></p>
</body>
</html>';
        exit;
    }
}
