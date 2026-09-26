<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Connexion à la base de données.
 *
 * Cette classe ne contient AUCUN identifiant : la configuration est
 * externalisée dans config/database.php (lui-même alimenté par le
 * fichier .env). Elle peut aussi recevoir la configuration
 * directement, ce qui facilite les tests.
 *
 * @package App\Core
 */
class Database
{
    /**
     * Jeu de caractères utilisé si la configuration ne le précise pas.
     *
     * @var string
     */
    private const DEFAULT_CHARSET = 'utf8mb4';

    /**
     * Connexion PDO active.
     *
     * @var PDO
     */
    private PDO $connection;

    /**
     * Constructeur.
     *
     * @param array<string, mixed>|null $config Configuration de connexion.
     *        Si null, elle est chargée depuis config/database.php.
     */
    public function __construct(?array $config = null)
    {
        $config ??= self::loadConfig();

        $this->connection = $this->connect($config);
    }

    /**
     * Retourne la connexion PDO active.
     *
     * @return PDO Connexion PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Charge la configuration depuis le fichier config/database.php.
     *
     * @return array<string, mixed> Configuration de la base de données
     * @throws RuntimeException Si le fichier est absent ou invalide
     */
    public static function loadConfig(): array
    {
        $path = CONFIG_PATH . '/database.php';

        if (!is_file($path)) {
            throw new RuntimeException(
                'Fichier de configuration de la base de données introuvable : ' . $path
            );
        }

        $config = require $path;

        if (!is_array($config)) {
            throw new RuntimeException(
                'Le fichier config/database.php doit retourner un tableau.'
            );
        }

        return $config;
    }

    /**
     * Établit la connexion PDO à partir de la configuration fournie.
     *
     * @param array<string, mixed> $config Configuration de connexion
     * @return PDO Connexion PDO établie
     * @throws RuntimeException Si la connexion échoue
     */
    private function connect(array $config): PDO
    {
        $driver   = (string) ($config['driver'] ?? 'mysql');
        $host     = (string) ($config['host'] ?? 'localhost');
        $port     = (int) ($config['port'] ?? 3306);
        $database = (string) ($config['database'] ?? '');
        $charset  = (string) ($config['charset'] ?? self::DEFAULT_CHARSET);

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $host,
            $port,
            $database,
            $charset
        );

        try {
            return new PDO(
                $dsn,
                (string) ($config['username'] ?? ''),
                (string) ($config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            // Le détail technique reste dans les logs serveur uniquement
            error_log('Erreur de connexion à la base de données : ' . $e->getMessage());

            throw new RuntimeException('Erreur de connexion à la base de données.');
        }
    }
}
