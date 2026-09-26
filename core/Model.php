<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Classe de base Model
 *
 * Fournit les opérations CRUD génériques (Create, Read, Update, Delete)
 * pour tous les modèles de l'application. Chaque modèle concret doit
 * étendre cette classe et définir le nom de la table correspondante.
 *
 * Toutes les requêtes utilisent des requêtes préparées afin de prévenir
 * les injections SQL.
 *
 * @package App\Core
 */
abstract class Model
{
    /**
     * Instance unique de connexion PDO partagée entre tous les modèles.
     *
     * @var PDO|null
     */
    private static ?PDO $connection = null;

    /**
     * Nom de la table associée au modèle.
     * Doit être redéfini dans chaque classe fille.
     *
     * @var string
     */
    protected string $table = '';

    /**
     * Nom de la colonne de clé primaire.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Liste des colonnes autorisées à être insérées/mises à jour
     * (protection contre l'assignation massive).
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Retourne la connexion PDO partagée.
     *
     * La connexion est créée une seule fois et réutilisée pour toutes
     * les instances de modèles de la requête courante (pattern singleton).
     *
     * @return PDO La connexion active
     * @throws RuntimeException Si la connexion échoue
     */
    protected function getConnection(): PDO
    {
        // Vérifie si la connexion n'a pas encore été établie
        if (self::$connection === null) {
            // Crée une nouvelle connexion via App\Core\Database
            $database = new Database();
            self::$connection = $database->getConnection();
        }

        return self::$connection;
    }

    /**
     * Vérifie si la table du modèle est bien définie.
     *
     * @throws RuntimeException Si le nom de la table est vide
     */
    private function ensureTable(): void
    {
        // Un modèle ne peut pas fonctionner sans nom de table
        if ($this->table === '') {
            throw new RuntimeException(
                'La propriété $table doit être définie dans le modèle ' . static::class
            );
        }
    }

    /**
     * Filtre les données pour ne conserver que les colonnes autorisées.
     *
     * @param array<string, mixed> $data Données brutes à filtrer
     * @return array<string, mixed> Données filtrées selon $fillable
     */
    private function filterFillable(array $data): array
    {
        // Si $fillable est vide, on retourne les données telles quelles
        if (empty($this->fillable)) {
            return $data;
        }

        // On ne conserve que les clés présentes dans la liste $fillable
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Récupère tous les enregistrements de la table.
     *
     * @return array<int, array<string, mixed>> Liste de tous les enregistrements
     */
    public function all(): array
    {
        $this->ensureTable();

        // Construction de la requête SELECT
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->getConnection()->query($sql);

        // Retourne tous les résultats sous forme de tableau associatif
        return $stmt->fetchAll();
    }

    /**
     * Récupère un enregistrement par sa clé primaire.
     *
     * @param int $id Identifiant de l'enregistrement
     * @return array<string, mixed>|null Données de l'enregistrement ou null si absent
     */
    public function find(int $id): ?array
    {
        $this->ensureTable();

        // Requête préparée pour éviter toute injection SQL
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        // Retourne l'enregistrement ou null s'il n'existe pas
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Récupère les enregistrements correspondant à une condition simple.
     *
     * @param string $column Nom de la colonne à filtrer
     * @param mixed $value Valeur recherchée
     * @return array<int, array<string, mixed>> Liste des enregistrements correspondants
     */
    public function where(string $column, mixed $value): array
    {
        $this->ensureTable();

        // Requête préparée avec un placeholder nommé
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':value' => $value]);

        // Retourne tous les résultats correspondants
        return $stmt->fetchAll();
    }

    /**
     * Insère un nouvel enregistrement dans la table.
     *
     * @param array<string, mixed> $data Données à insérer (filtrées par $fillable)
     * @return int Identifiant du nouvel enregistrement
     */
    public function create(array $data): int
    {
        $this->ensureTable();

        // Filtre les données pour ne garder que les colonnes autorisées
        $data = $this->filterFillable($data);

        // Construction de la requête d'insertion dynamique
        $columns = array_keys($data);
        $placeholders = array_map(fn(string $col) => ":{$col}", $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        // Préparation et exécution de la requête
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($data);

        // Retourne l'ID du dernier enregistrement inséré
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * Met à jour un enregistrement existant.
     *
     * @param int $id Identifiant de l'enregistrement à modifier
     * @param array<string, mixed> $data Nouvelles valeurs (filtrées par $fillable)
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function update(int $id, array $data): bool
    {
        $this->ensureTable();

        // Filtre les données pour ne garder que les colonnes autorisées
        $data = $this->filterFillable($data);

        // Aucune donnée valide à mettre à jour
        if (empty($data)) {
            return false;
        }

        // Construction de la clause SET (colonne = :colonne)
        $sets = array_map(fn(string $col) => "{$col} = :{$col}", array_keys($data));
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :id',
            $this->table,
            implode(', ', $sets),
            $this->primaryKey
        );

        // Ajoute l'identifiant aux paramètres de la requête
        $data[':id'] = $id;

        // Préparation et exécution de la requête
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Supprime un enregistrement par sa clé primaire.
     *
     * @param int $id Identifiant de l'enregistrement à supprimer
     * @return bool True si la suppression a réussi, false sinon
     */
    public function delete(int $id): bool
    {
        $this->ensureTable();

        // Requête préparée pour la suppression
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':id' => $id]);

        // Retourne true si au moins une ligne a été supprimée
        return $stmt->rowCount() > 0;
    }
}