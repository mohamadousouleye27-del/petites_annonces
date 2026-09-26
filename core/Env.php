<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Chargeur de fichier .env minimaliste.
 *
 * Lit un fichier .env (format clé=valeur) et expose les valeurs via une
 * API statique typée. Aucune dépendance externe (pas de vlucas/phpdotenv) :
 * l'application reste installable hors ligne.
 *
 * Ordre de résolution d'une variable :
 *   1. valeur du fichier .env chargé ;
 *   2. variable déjà présente dans $_ENV / $_SERVER / getenv() ;
 *   3. valeur par défaut fournie par l'appelant.
 *
 * @package App\Core
 */
class Env
{
    /**
     * Variables issues du fichier .env.
     *
     * @var array<string, string>
     */
    private static array $vars = [];

    /**
     * Indique si un fichier .env a déjà été traité.
     *
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Charge un fichier .env (une seule fois par requête).
     *
     * Un fichier absent ou illisible est ignoré silencieusement : les
     * valeurs par défaut des fichiers de configuration prennent alors
     * le relais.
     *
     * @param string $path Chemin absolu du fichier .env
     * @return void
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignore les commentaires et les lignes vides
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Supprime un éventuel préfixe « export »
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }

            // Une ligne valide doit contenir un séparateur « = »
            $position = strpos($line, '=');

            if ($position === false) {
                continue;
            }

            $key = trim(substr($line, 0, $position));
            $value = trim(substr($line, $position + 1));

            if ($key === '') {
                continue;
            }

            self::$vars[$key] = self::unquote($value);
        }
    }

    /**
     * Récupère la valeur brute d'une variable.
     *
     * @param string $key Nom de la variable
     * @param mixed $default Valeur retournée si la variable est absente
     * @return mixed Valeur trouvée ou $default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$vars)) {
            return self::$vars[$key];
        }

        // Variables déjà injectées par le serveur web ou la CLI
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value !== null) {
            return $value;
        }

        $getenv = getenv($key);

        return $getenv !== false ? $getenv : $default;
    }

    /**
     * Récupère une variable sous forme de chaîne.
     *
     * @param string $key Nom de la variable
     * @param string $default Valeur par défaut
     * @return string Valeur sous forme de chaîne
     */
    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Récupère une variable sous forme d'entier.
     *
     * @param string $key Nom de la variable
     * @param int $default Valeur par défaut
     * @return int Valeur sous forme d'entier
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Récupère une variable sous forme de booléen.
     *
     * Sont considérées comme vraies : 1, true, yes, on, oui
     * (insensible à la casse).
     *
     * @param string $key Nom de la variable
     * @param bool $default Valeur par défaut
     * @return bool Valeur sous forme de booléen
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on', 'oui'], true);
    }

    /**
     * Retire les guillemets englobants d'une valeur.
     *
     * @param string $value Valeur brute
     * @return string Valeur sans guillemets
     */
    private static function unquote(string $value): string
    {
        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];

            if (($first === '"' && $last === '"')
                || ($first === "'" && $last === "'")
            ) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}
