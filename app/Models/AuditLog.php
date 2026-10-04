<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle AuditLog — LECTURE SEULE.
 *
 * Table `audit_logs` (PK char(36), UUID).
 *
 * `user_id` est NULLABLE (FK ON DELETE SET NULL) : les libellés d'auteur
 * sont donc résolus par LEFT JOIN, afin de ne jamais écarter une entrée
 * dont le compte a été supprimé. Une entrée sans auteur reste lisible.
 *
 * Le schéma réel ne comporte AUCUNE colonne `target_id` : la cible est
 * décrite par `description` (texte libre), et catégorisée par `target_type`.
 * Aucune colonne n'est inventée ici.
 *
 * PALIER 7.0 : lecture seule. L'écriture d'une entrée d'audit est faite par
 * le serveur lors des actions métier (palier ultérieur).
 *
 * @package App\Models
 */
class AuditLog extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'audit_logs';

    /**
     * Nom de la colonne de clé primaire (UUID char(36)).
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Nombre maximal de lignes retournées.
     *
     * @var int
     */
    private const LIMITE_MAX = 200;

    /**
     * Colonnes de sélection communes (chaîne constante).
     *
     * @var string
     */
    private const COLONNES = 'l.id, l.user_id, l.action, l.description,
            l.ip_address, l.created_at, l.target_type,
            u.prenom AS auteur_prenom,
            u.nom AS auteur_nom,
            u.role AS auteur_role';

    /**
     * Jointure : LEFT JOIN, car `audit_logs.user_id` peut être NULL.
     *
     * @var string
     */
    private const DEPUIS = 'FROM audit_logs l
            LEFT JOIN users u ON u.id = l.user_id';

    /**
     * Liste les entrées du journal, les plus récentes d'abord.
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Entrées (tableau vide si aucune)
     */
    public function listerTous(int $limite = 100): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' ORDER BY l.created_at DESC, l.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les entrées du journal attribuées à un utilisateur donné.
     *
     * Utilisé pour le « journal personnel » du modérateur : il ne voit que
     * ses propres actions.
     *
     * @param string $userId UUID de l'utilisateur
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Entrées (tableau vide si aucune)
     */
    public function listerParUtilisateur(string $userId, int $limite = 50): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE l.user_id = :user_id'
            . ' ORDER BY l.created_at DESC, l.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les entrées du journal portant sur un type de cible donné.
     *
     * Remplace, pour l'écran « dernières décisions », l'absence de colonne
     * `updated_at` sur `annonces` : les décisions sont tracées ici.
     *
     * @param string $targetType Type de cible ('annonce', 'signalement', 'utilisateur', 'categorie')
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Entrées (tableau vide si aucune)
     */
    public function listerParTypeCible(string $targetType, int $limite = 50): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE l.target_type = :target_type'
            . ' ORDER BY l.created_at DESC, l.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':target_type', $targetType, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte les entrées postérieures à une date donnée.
     *
     * La date seuil est calculée en PHP puis transmise en paramètre lié :
     * aucune expression temporelle n'est interpolée dans le SQL.
     *
     * @param string $depuis Date seuil au format 'Y-m-d H:i:s'
     * @return int Nombre d'entrées (0 si aucune)
     */
    public function compterDepuis(string $depuis): int
    {
        $sql = 'SELECT COUNT(*) FROM audit_logs l WHERE l.created_at >= :depuis';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':depuis', $depuis, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

/**
     * Compte les actions d'audit attribuées à un utilisateur donné.
     *
     * Utilisé par les profils modérateur et administrateur : compte les
     * décisions/actions journalisées pour CE compte uniquement.
     *
     * LECTURE SEULE : requête préparée, aucune écriture.
     *
     * @param string $userId UUID de l'utilisateur
     * @return int Nombre d'entrées (0 si aucune)
     */
    public function compterParUtilisateur(string $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM audit_logs l WHERE l.user_id = :user_id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Borne une limite demandée entre 1 et LIMITE_MAX.
     *
     * @param int $limite Limite demandée
     * @return int Limite effective
     */
    private function bornerLimite(int $limite): int
    {
        if ($limite < 1) {
            return 1;
        }

        return $limite > self::LIMITE_MAX ? self::LIMITE_MAX : $limite;
    }
}