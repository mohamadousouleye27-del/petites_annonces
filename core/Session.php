<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gestion centralisée des sessions.
 *
 * Fournit une API simple et statique pour manipuler $_SESSION :
 * démarrage, lecture, écriture, suppression, messages flash
 * et destruction complète de la session.
 *
 * @package App\Core
 */
class Session
{
    /**
     * Durée de vie de la session en secondes (2 heures).
     *
     * @var int
     */
    private const SESSION_LIFETIME = 7200;

    /**
     * Clé racine utilisée pour stocker les messages flash.
     *
     * @var string
     */
    private const FLASH_KEY = '_flash';

    /**
     * Démarre la session si elle n'est pas déjà active.
     *
     * Configure les paramètres de cookie avant le démarrage :
     *   - httponly : le cookie est inaccessible depuis JavaScript
     *   - samesite : Lax (protection CSRF de base)
     *   - secure   : uniquement si la requête est en HTTPS
     *   - durée    : 2 heures
     *
     * @return void
     */
    public static function start(): void
    {
        // Ne démarre pas la session si elle est déjà active
        // (2 = PHP_SESSION_ACTIVE)
        if (session_status() === 2) {
            return;
        }

        $secure = self::isHttps();

        // Configure les paramètres du cookie de session
        // (doit être fait avant session_start())
        session_set_cookie_params([
            'lifetime' => self::SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Aligne la durée de vie côté serveur sur celle du cookie.
        // NB : l'option ini s'écrit bien « session.gc_maxlifetime »
        // (sans underscore entre « max » et « lifetime »).
        ini_set('session.gc_maxlifetime', (string) self::SESSION_LIFETIME);

        // Démarre la session
        session_start();
    }

    /**
     * Définit une valeur dans la session.
     *
     * @param string $key Clé de la valeur
     * @param mixed $value Valeur à stocker
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Récupère une valeur de la session.
     *
     * @param string $key Clé de la valeur
     * @param mixed $default Valeur retournée si la clé est absente
     * @return mixed Valeur stockée ou $default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vérifie si une clé existe dans la session.
     *
     * @param string $key Clé à vérifier
     * @return bool True si la clé existe, false sinon
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Supprime une valeur de la session.
     *
     * @param string $key Clé à supprimer
     * @return void
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Stocke un message flash.
     *
     * Le message sera disponible pour la requête suivante via getFlash().
     * Catégories recommandées : success, error, warning, info.
     *
     * @param string $key Catégorie du message (ex: 'success', 'error')
     * @param mixed $message Contenu du message
     * @return void
     */
    public static function flash(string $key, mixed $message): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $message;
    }

    /**
     * Récupère un message flash puis le supprime.
     *
     * Le message n'est disponible que pour la requête suivante :
     * après lecture, il est automatiquement retiré de la session.
     *
     * @param string $key Catégorie du message
     * @param mixed $default Valeur retournée si aucun message n'est présent
     * @return mixed Message flash ou $default
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $flashes = $_SESSION[self::FLASH_KEY] ?? [];

        if (!isset($flashes[$key])) {
            return $default;
        }

        $message = $flashes[$key];

        // Consomme le message : il ne sera plus disponible
        unset($flashes[$key]);
        $_SESSION[self::FLASH_KEY] = $flashes;

        return $message;
    }

    /**
     * Vérifie si un message flash existe sans le consommer.
     *
     * @param string $key Catégorie du message
     * @return bool True si un message flash existe, false sinon
     */
    public static function hasFlash(string $key): bool
    {
        $flashes = $_SESSION[self::FLASH_KEY] ?? [];
        return isset($flashes[$key]);
    }

    /**
     * Vide toutes les données de session.
     *
     * La session reste active mais ne contient plus aucune donnée.
     *
     * @return void
     */
    public static function clear(): void
    {
        session_unset();
    }

    /**
     * Détruit complètement la session.
     *
     * Vide les données, supprime le cookie de session
     * et appelle session_destroy().
     *
     * @return void
     */
    public static function destroy(): void
    {
        $sessionName = session_name();
        $secure = self::isHttps();

        // Vide les données de session
        session_unset();

        // Détruit la session côté serveur
        session_destroy();

        // Supprime le cookie de session côté client
        setcookie($sessionName, '', [
            'expires' => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Détecte si la requête courante utilise HTTPS.
     *
     * Compatible avec un serveur local HTTP (XAMPP) et
     * une future mise en production en HTTPS.
     *
     * @return bool True si la requête est en HTTPS, false sinon
     */
    private static function isHttps(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? '';
        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

        return strtolower($https) === 'on'
            || strtolower($scheme) === 'https'
            || strtolower($forwarded) === 'https';
    }
}