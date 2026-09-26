<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

/**
 * Classe de base des middlewares.
 *
 * Un middleware est exécuté par App\Core\Router AVANT l'action du
 * contrôleur. Deux issues possibles :
 *
 *   - il autorise la poursuite : handle() se termine normalement et le
 *     middleware suivant (ou l'action du contrôleur) est exécuté ;
 *   - il refuse : il interrompt la requête (redirection HTTP 302 ou
 *     réponse HTTP 403) et l'action du contrôleur n'est JAMAIS atteinte.
 *
 * Les middlewares ne lisent que la session serveur : aucune donnée
 * fournie par le client ($_GET, $_POST, en-tête, cookie, JavaScript)
 * n'est utilisée pour décider d'une autorisation.
 *
 * @package App\Middleware
 */
abstract class Middleware
{
    /**
     * Point d'entrée du middleware.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Indique si un utilisateur est authentifié.
     *
     * S'appuie uniquement sur la clé `user_id` de la session serveur.
     *
     * @return bool True si un utilisateur est connecté, false sinon
     */
    protected function isAuthenticated(): bool
    {
        return Session::has('user_id');
    }

    /**
     * Rôle de l'utilisateur connecté.
     *
     * Toute valeur non textuelle ou vide est ramenée à une chaîne vide,
     * ce qui équivaut à « aucun rôle ».
     *
     * @return string Rôle courant ('member', 'moderateur', 'admin') ou chaîne vide
     */
    protected function currentRole(): string
    {
        $role = Session::get('user_role');

        return is_string($role) ? trim($role) : '';
    }

    /**
     * Redirige le client puis termine immédiatement l'exécution.
     *
     * @param string $url URL de destination
     * @return never
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Renvoie une réponse HTTP 403 générique puis termine l'exécution.
     *
     * La page est volontairement neutre : ni rôle requis, ni rôle de
     * l'utilisateur, ni donnée de session, ni trace technique.
     *
     * @return never
     */
    protected function forbidden(): never
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Accès interdit</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        h1 { color: #c0392b; font-size: 48px; margin-bottom: 10px; }
        p { color: #666; font-size: 18px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>403</h1>
    <p>Accès interdit : vous n\'avez pas les droits nécessaires pour accéder à cette page.</p>
    <p><a href="' . base_path('/') . '">Retour à l\'accueil</a></p>
</body>
</html>';

        exit;
    }
}
