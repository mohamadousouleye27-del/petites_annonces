<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF centralisée.
 *
 * Fournit une API simple et statique pour générer, récupérer, afficher
 * et valider un token CSRF stocké dans la session via App\Core\Session.
 *
 * Exemple d'utilisation dans une vue :
 *
 *   <form method="POST" action="/petites_annonces/auth/register">
 *       <?= Csrf::field() ?>
 *       ...
 *   </form>
 *
 * Exemple de vérification dans un contrôleur :
 *
 *   if (!Csrf::validate($_POST['_csrf'] ?? null)) {
 *       // rejet de la requête
 *   }
 *
 * Le token est généré avec random_bytes() (source aléatoire
 * cryptographiquement sûre) et comparé avec hash_equals() (comparaison
 * résistante aux timing attacks).
 *
 * @package App\Core
 */
class Csrf
{
    /**
     * Clé de session utilisée pour stocker le token CSRF.
     *
     * @var string
     */
    private const TOKEN_KEY = '_csrf_token';

    /**
     * Nom du champ de formulaire utilisé pour transmettre le token.
     *
     * @var string
     */
    private const FIELD_NAME = '_csrf';

    /**
     * Nombre d'octets aléatoires utilisés pour générer le token.
     *
     * 32 octets encodés en hexadécimal produisent une chaîne de
     * 64 caractères (256 bits d'entropie).
     *
     * @var int
     */
    private const TOKEN_BYTES = 32;

    /**
     * Retourne le token CSRF courant.
     *
     * Le token est généré une seule fois puis réutilisé pendant toute
     * la durée de la session : plusieurs appels successifs retournent
     * la même valeur.
     *
     * @return string Token CSRF courant
     */
    public static function token(): string
    {
        self::ensureSession();

        $token = Session::get(self::TOKEN_KEY);

        // Réutilise le token existant s'il est valide
        if (is_string($token) && $token !== '') {
            return $token;
        }

        // Sinon, génère et stocke un nouveau token
        $token = self::generateToken();
        Session::set(self::TOKEN_KEY, $token);

        return $token;
    }

    /**
     * Génère le champ HTML caché contenant le token CSRF.
     *
     * Le token est échappé pour être sûr lorsqu'il est injecté dans
     * une vue :
     *
     *   <input type="hidden" name="_csrf" value="TOKEN">
     *
     * @return string Champ HTML caché prêt à être affiché
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::FIELD_NAME,
            $token
        );
    }

    /**
     * Retourne le nom du champ utilisé pour transmettre le token.
     *
     * Utile pour lire la valeur reçue en POST :
     *
     *   $_POST[Csrf::fieldName()]
     *
     * @return string Nom du champ CSRF
     */
    public static function fieldName(): string
    {
        return self::FIELD_NAME;
    }

    /**
     * Vérifie un token CSRF reçu.
     *
     * Retourne true uniquement si le token fourni est une chaîne non vide
     * correspondant exactement au token stocké en session. La comparaison
     * utilise hash_equals() afin de résister aux timing attacks.
     *
     * La méthode est robuste face aux types inattendus (null, tableau,
     * entier...) : elle échoue proprement sans warning, notice ni erreur
     * fatale.
     *
     * Le token n'est pas détruit après une validation réussie : il reste
     * réutilisable pour les formulaires suivants de la même session.
     *
     * @param mixed $token Token reçu (ex: $_POST['_csrf'] ?? null)
     * @return bool True si le token est valide, false sinon
     */
    public static function validate(mixed $token): bool
    {
        // Rejette null, tableaux, entiers, booléens et chaînes vides
        if (!is_string($token) || $token === '') {
            return false;
        }

        self::ensureSession();

        $stored = Session::get(self::TOKEN_KEY);

        // Aucun token stocké (ou valeur interne inattendue) : échec
        if (!is_string($stored) || $stored === '') {
            return false;
        }

        // Comparaison résistante aux timing attacks
        return hash_equals($stored, $token);
    }

    /**
     * Garantit qu'une session est active.
     *
     * Délègue à App\Core\Session, qui ne démarre la session que si
     * elle n'est pas déjà active : aucun risque de double démarrage.
     *
     * @return void
     */
    private static function ensureSession(): void
    {
        Session::start();
    }

    /**
     * Génère un nouveau token CSRF aléatoire.
     *
     * Utilise random_bytes(), source aléatoire cryptographiquement sûre.
     * Les 32 octets générés sont encodés en hexadécimal, produisant une
     * chaîne de 64 caractères imprévisibles.
     *
     * @return string Token CSRF généré
     */
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }
}
