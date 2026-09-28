<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Utilitaires liés aux rôles : résolution d'URL et libellés d'affichage.
 *
 * PÉRIMÈTRE STRICT — cette classe ne fait QUE deux choses :
 *   - convertir un rôle technique en chemin de destination (dashboardPath()) ;
 *   - convertir un rôle technique en libellé français (label()).
 *
 * Elle ne contient AUCUNE logique d'authentification, de mot de passe, de
 * session, de cookie ni de contrôle d'accès. Les responsabilités restent
 * séparées :
 *
 *   AuthController      → authentification (password_verify, ouverture de session)
 *   Session             → gestion de la session
 *   AuthMiddleware      → « l'utilisateur est-il connecté ? »
 *   RoleMiddleware      → « le rôle est-il autorisé ? » (seule autorité de décision)
 *   Auth (cette classe) → utilitaires de rôle : URL de destination, libellé
 *
 * Aucune méthode d'ici ne décide d'un accès : dashboardPath() ne construit
 * qu'une URL et label() qu'une chaîne affichable. Elles sont appelées APRÈS
 * qu'une décision a été prise (connexion réussie, ou utilisateur déjà
 * authentifié), jamais pour la prendre.
 *
 * SÉCURITÉ : la comparaison des rôles est STRICTE et sensible à la casse,
 * sur les valeurs exactes de l'ENUM `users.role` : 'member', 'moderateur',
 * 'admin'. Toute autre valeur (null, chaîne vide, 'ADMIN', 'root', espaces
 * parasites seuls...) est considérée comme inconnue : dashboardPath()
 * renvoie alors l'espace public et label() une chaîne vide. Aucun rôle
 * inattendu ne peut donc donner accès à un espace.
 *
 * @package App\Core
 */
final class Auth
{
    /**
     * Rôle technique → segment applicatif de l'espace correspondant.
     *
     * Les clés sont les valeurs exactes de l'ENUM `users.role`.
     *
     * @var array<string, string>
     */
    private const ESPACES = [
        'member'     => 'membre',
        'moderateur' => 'moderateur',
        'admin'      => 'admin',
    ];

    /**
     * Rôle technique → libellé français affichable.
     *
     * @var array<string, string>
     */
    private const LIBELLES = [
        'member'     => 'Membre',
        'moderateur' => 'Modérateur',
        'admin'      => 'Administrateur',
    ];

    /**
     * Classe utilitaire : non instanciable.
     */
    private function __construct()
    {
    }

    /**
     * Chemin public de l'espace correspondant à un rôle.
     *
     * Le chemin retourné est un chemin public COMPLET, incluant le préfixe de
     * l'application (ex: « /petites_annonces/admin ») : il est utilisable tel
     * quel dans un en-tête « Location » (Controller::redirect(),
     * Middleware::redirect()).
     *
     * Un rôle inconnu, vide ou non textuel renvoie l'espace public :
     * aucun espace protégé n'est jamais atteint par défaut.
     *
     * @param string|null $role Rôle technique ('member', 'moderateur', 'admin')
     * @return string Chemin public complet de destination
     */
    public static function dashboardPath(?string $role): string
    {
        $role = self::normaliser($role);

        if ($role === null || !array_key_exists($role, self::ESPACES)) {
            return base_path('/');
        }

        return base_path(self::ESPACES[$role]);
    }

    /**
     * Libellé français d'un rôle, destiné à l'affichage.
     *
     * Un rôle inconnu, vide ou non textuel renvoie une chaîne vide : l'appelant
     * décide de la valeur de repli à afficher.
     *
     * @param string|null $role Rôle technique ('member', 'moderateur', 'admin')
     * @return string Libellé français, ou chaîne vide si le rôle est inconnu
     */
    public static function label(?string $role): string
    {
        $role = self::normaliser($role);

        if ($role === null) {
            return '';
        }

        return self::LIBELLES[$role] ?? '';
    }

    /**
     * Normalise un rôle reçu avant toute comparaison.
     *
     * Seules les chaînes non vides (après suppression des espaces en début et
     * fin) sont exploitables. Toute autre valeur — null, tableau, entier,
     * chaîne vide — équivaut à « aucun rôle » et ne donne accès à rien.
     *
     * La casse n'est PAS normalisée : 'ADMIN' n'est pas 'admin'.
     *
     * @param string|null $role Rôle reçu
     * @return string|null Rôle exploitable, ou null si aucun rôle valable
     */
    private static function normaliser(?string $role): ?string
    {
        if (!is_string($role)) {
            return null;
        }

        $role = trim($role);

        return $role === '' ? null : $role;
    }
}
