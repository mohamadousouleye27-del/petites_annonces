<?php

declare(strict_types=1);

/**
 * Fonctions utilitaires globales.
 *
 * Ce fichier est chargé automatiquement par Composer (clé « files »
 * de composer.json) et, en attendant, manuellement par public/index.php.
 *
 * Les fonctions sont protégées par function_exists() afin de rester
 * sûres même en cas de double inclusion.
 *
 * @package Core
 */

if (!function_exists('config')) {
    /**
     * Accède à la configuration de l'application.
     *
     * Les fichiers config/app.php et config/database.php sont chargés
     * paresseusement (une seule fois) puis mis en cache.
     *
     * @param string|null $key Clé au format « fichier.cle.souscle » (ex: 'app.name')
     * @param mixed $default Valeur retournée si la clé est absente
     * @return mixed Configuration complète (si $key est null) ou valeur ciblée
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];

            foreach (['app', 'database'] as $name) {
                $path = CONFIG_PATH . '/' . $name . '.php';

                if (is_file($path)) {
                    $cache[$name] = require $path;
                }
            }
        }

        if ($key === null) {
            return $cache;
        }

        $value = $cache;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    /**
     * Construit un chemin public relatif à la racine de l'application.
     *
     * Exemples (base_path = /petites_annonces) :
     *   base_path()              → /petites_annonces
     *   base_path('/')           → /petites_annonces/
     *   base_path('auth/login')  → /petites_annonces/auth/login
     *
     * @param string $path Chemin à ajouter
     * @return string Chemin public relatif
     */
    function base_path(string $path = ''): string
    {
        $base = (string) config('app.base_path', '');

        if ($path === '') {
            return $base === '' ? '' : $base;
        }

        if ($path === '/') {
            return $base . '/';
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Construit une URL absolue à partir de l'adresse de l'application.
     *
     * Exemple (APP_URL = http://localhost/petites_annonces) :
     *   url('auth/login') → http://localhost/petites_annonces/auth/login
     *
     * @param string $path Chemin à ajouter
     * @return string URL absolue
     */
    function url(string $path = ''): string
    {
        $base = (string) config('app.url', '');

        return $path === '' ? $base . '/' : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Construit l'URL publique d'un fichier statique (CSS, JS, image).
     *
     * Les fichiers statiques vivent dans public/assets/.
     *
     * Exemple :
     *   asset('assets/css/auth.css') → /petites_annonces/public/assets/css/auth.css
     *
     * @param string $path Chemin du fichier dans public/
     * @return string URL relative du fichier
     */
    function asset(string $path): string
    {
        return base_path('/public/' . ltrim($path, '/'));
    }
}
