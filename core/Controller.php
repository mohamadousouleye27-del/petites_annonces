<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Classe de base Controller
 *
 * Fournit les méthodes utilitaires communes à tous les contrôleurs
 * de l'application : chargement de vues, redirections, réponses JSON
 * et gestion des entrées de la requête HTTP.
 *
 * Chaque contrôleur concret doit étendre cette classe.
 *
 * @package App\Core
 */
abstract class Controller
{
    /**
     * Charge une vue et lui transmet les données.
     *
     * Le tableau $data est extrait en variables locales : chaque clé du
     * tableau devient une variable accessible dans le fichier de vue.
     * Exemple : ['annonces' => $annonces] → $annonces dans la vue.
     *
     * @param string $view Chemin de la vue (relatif au dossier app/views, sans extension .php)
     * @param array<string, mixed> $data Données à transmettre à la vue
     * @return void
     */
    protected function view(string $view, array $data = []): void
    {
        // Transformation des clés en variables accessibles dans la vue
        extract($data, EXTR_SKIP);

        // Chemin complet du fichier de vue
        // NB : le dossier app/Views respecte la casse (PSR-4 / Linux).
        $viewPath = APP_PATH . '/Views/' . $view . '.php';

        // Vérifie que le fichier de vue existe avant de le charger
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("La vue '{$view}' est introuvable : {$viewPath}");
        }

        // Inclusion du fichier de vue
        require $viewPath;
    }

    /**
     * Redirige l'utilisateur vers une autre URL.
     *
     * @param string $url URL de destination (peut être un chemin relatif ou absolu)
     * @return never
     */
    protected function redirect(string $url): never
    {
        // Envoie l'en-tête HTTP de redirection
        header('Location: ' . $url);
        // Arrête immédiatement l'exécution du script
        exit;
    }

    /**
     * Envoie une réponse JSON au client.
     *
     * Utilisée pour les requêtes AJAX/API où le client attend
     * une réponse au format JSON.
     *
     * @param mixed $data Données à encoder en JSON
     * @param int $status Code de statut HTTP (défaut : 200 OK)
     * @return never
     */
    protected function json(mixed $data, int $status = 200): never
    {
        // Définit le code de statut HTTP
        http_response_code($status);

        // Définit le type de contenu comme étant du JSON
        header('Content-Type: application/json; charset=utf-8');

        // Encode les données en JSON et les affiche
        echo json_encode($data);

        // Arrête l'exécution du script
        exit;
    }

    /**
     * Vérifie si la requête courante est de type POST.
     *
     * @return bool True si la requête est un POST, false sinon
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Vérifie si la requête courante est de type GET.
     *
     * @return bool True si la requête est un GET, false sinon
     */
    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Récupère les données de la requête (GET et POST combinés).
     *
     * @return array<string, mixed> Tableau des paramètres de la requête
     */
    protected function request(): array
    {
        // Fusionne les paramètres GET et POST (POST a la priorité)
        return array_merge($_GET, $_POST);
    }

    /**
     * Récupère une valeur de la requête GET.
     *
     * @param string $key Nom du paramètre
     * @param mixed $default Valeur par défaut si le paramètre est absent
     * @return mixed Valeur du paramètre ou la valeur par défaut
     */
    protected function getInput(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Récupère une valeur de la requête POST.
     *
     * @param string $key Nom du paramètre
     * @param mixed $default Valeur par défaut si le paramètre est absent
     * @return mixed Valeur du paramètre ou la valeur par défaut
     */
    protected function postInput(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Récupère une valeur envoyée en JSON dans le corps de la requête.
     *
     * Utilisée pour les requêtes fetch() avec Content-Type: application/json.
     *
     * @return array<string, mixed> Données JSON décodées
     */
    protected function jsonInput(): array
    {
        // Lit le corps brut de la requête
        $raw = file_get_contents('php://input');

        // Décode le JSON (retourne un tableau vide si le JSON est invalide)
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Vérifie si l'utilisateur est authentifié.
     *
     * Cette méthode est un placeholder de base qui peut être surchargée
     * dans les contrôleurs concrets pour implémenter la logique d'auth.
     *
     * @return bool True si un utilisateur est connecté, false sinon
     */
    protected function isAuthenticated(): bool
    {
        // Vérifie la présence de l'identifiant utilisateur en session
        return isset($_SESSION['user_id']);
    }

    /**
     * Exige une session authentifiée pour poursuivre l'exécution.
     *
     * À appeler en tête des actions réservées aux utilisateurs connectés :
     *
     *   $this->requireAuth();
     *   // Si cette ligne est atteinte, l'utilisateur est authentifié.
     *
     * Comportement :
     *   - session authentifiée (clé `user_id` présente) : ne fait rien et
     *     rend immédiatement la main au contrôleur appelant ;
     *   - aucune session authentifiée : redirection HTTP 302 vers
     *     /petites_annonces/auth/login via le mécanisme existant
     *     Controller::redirect(), puis arrêt de l'exécution.
     *
     * La vérification s'appuie uniquement sur l'API publique de
     * App\Core\Session : aucun accès direct à $_SESSION, aucune requête
     * SQL, aucun chargement de modèle et aucune donnée utilisateur
     * n'est lue ou exposée.
     *
     * Seul l'état « connecté / non connecté » est contrôlé ici : les rôles
     * (member, moderateur, admin), le statut du compte, les permissions et
     * la propriété des ressources relèvent d'étapes séparées.
     *
     * @return void
     */
    protected function requireAuth(): void
    {
        // Vérifie la présence de l'identifiant utilisateur en session
        // (méthode publique existante de App\Core\Session)
        if (Session::has('user_id')) {
            // Utilisateur authentifié : le contrôleur appelant continue
            return;
        }

        // Utilisateur non authentifié : redirection vers la page de connexion
        // (Controller::redirect() envoie un en-tête Location → HTTP 302
        //  puis termine le script)
        $this->redirect(base_path('auth/login'));

        // Sécurité : si redirect() ne terminait plus l'exécution, la méthode
        // doit s'arrêter ici et ne jamais laisser passer un utilisateur anonyme.
        return;
    }

    /**
     * Rôle de l'utilisateur actuellement connecté.
     *
     * Le rôle est lu exclusivement depuis la session serveur (clé
     * `user_role`, renseignée à la connexion par AuthController à partir de
     * la colonne `users.role`). Aucune donnée fournie par le client
     * ($_GET, $_POST, paramètre d'URL, champ caché, JavaScript) n'est
     * utilisée : le client ne peut donc jamais choisir son propre rôle.
     *
     * Toute valeur non textuelle ou vide (tableau, entier, chaîne vide...)
     * est ramenée à une chaîne vide, ce qui équivaut à « aucun rôle ».
     *
     * @return string Rôle courant ('member', 'moderateur', 'admin') ou chaîne vide
     */
    protected function currentRole(): string
    {
        $role = Session::get('user_role');

        // Seule une chaîne est exploitable ; les espaces parasites sont retirés
        return is_string($role) ? trim($role) : '';
    }

    /**
     * Exige que l'utilisateur authentifié possède l'un des rôles autorisés.
     *
     * À appeler en tête d'une action réservée à certains rôles :
     *
     *   $this->requireRole('admin');                    // admin uniquement
     *   $this->requireRole(['admin', 'moderateur']);    // admin ou moderateur
     *
     * Comportement :
     *   1. délègue le contrôle d'authentification au mécanisme existant
     *      requireAuth() (aucune logique d'authentification dupliquée) ;
     *   2. lit le rôle courant exclusivement depuis la session serveur via
     *      currentRole() ;
     *   3. autorise l'exécution si le rôle courant figure dans la liste
     *      attendue (comparaison stricte et sensible à la casse, les rôles
     *      de référence étant exactement `member`, `moderateur` et `admin`) ;
     *   4. refuse l'accès dans le cas contraire (voir forbidden()).
     *
     * Un rôle unique est accepté aussi bien qu'un tableau de rôles. Un
     * tableau vide n'autorise personne : requireRole([]) refuse toujours
     * l'accès (comportement « fail-safe »).
     *
     * Le refus d'accès ne détruit pas la session, ne modifie aucune donnée
     * de session (`user_id`, `user_prenom`, `user_role`...), ne redirige
     * pas vers la page de connexion (l'utilisateur est déjà authentifié) et
     * n'exécute aucune requête SQL.
     *
     * Distinction des deux refus :
     *
     *   non connecté               → 302 vers /petites_annonces/auth/login
     *                                (via requireAuth())
     *   connecté, rôle insuffisant → 403 (via forbidden())
     *
     * @param string|array<int, string> $roles Rôle unique ou liste des rôles autorisés
     * @return void
     */
    protected function requireRole(string|array $roles): void
    {
        // 1. Authentification : un visiteur anonyme est redirigé (302) vers
        //    la page de connexion par requireAuth(), puis l'exécution s'arrête.
        $this->requireAuth();

        // 2. Rôle courant : uniquement depuis la session serveur
        $currentRole = $this->currentRole();

        // 3. Rôles autorisés normalisés (rôle unique → tableau)
        $allowedRoles = $this->normalizeRoles($roles);

        // 4. Autorisation : le rôle courant doit figurer dans la liste
        if ($currentRole !== '' && in_array($currentRole, $allowedRoles, true)) {
            // Accès autorisé : le contrôleur appelant poursuit son exécution
            return;
        }

        // 5. Refus : réponse HTTP 403 générique, session laissée intacte
        $this->forbidden();
    }

    /**
     * Normalise la liste des rôles autorisés.
     *
     * Accepte un rôle unique ou un tableau de rôles et ne conserve que les
     * entrées réellement exploitables : chaînes non vides, sans espaces
     * parasites. Toute autre entrée (tableau imbriqué, entier, null...) est
     * ignorée afin qu'une valeur inattendue ne puisse jamais élargir les
     * droits accordés.
     *
     * @param string|array<int, string> $roles Rôle unique ou liste de rôles
     * @return array<int, string> Liste normalisée des rôles autorisés
     */
    private function normalizeRoles(string|array $roles): array
    {
        // Un rôle unique est traité comme une liste d'un seul élément
        $roles = is_array($roles) ? $roles : [$roles];

        $normalized = [];

        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }

            $role = trim($role);

            if ($role !== '') {
                $normalized[] = $role;
            }
        }

        return $normalized;
    }

    /**
     * Renvoie une réponse HTTP 403 Forbidden générique.
     *
     * Appelée lorsqu'un utilisateur authentifié ne possède pas le rôle
     * requis. La réponse est volontairement minimale et neutre : elle
     * n'indique ni le rôle requis, ni le rôle de l'utilisateur, ni aucune
     * donnée de session, et n'expose ni trace technique, ni chemin serveur.
     *
     * Aucune écriture n'est effectuée en session (pas de message flash) :
     * la session reste strictement identique et l'utilisateur reste
     * connecté. Aucune requête SQL n'est exécutée. Aucune redirection n'est
     * envoyée : un utilisateur authentifié n'est jamais renvoyé vers la
     * page de connexion.
     *
     * @return never
     */
    private function forbidden(): never
    {
        // Code HTTP 403 : accès refusé
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');

        // Page statique : aucune donnée utilisateur ni de session n'y est injectée
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

        // Arrêt immédiat : l'action protégée n'est jamais exécutée
        exit;
    }
}