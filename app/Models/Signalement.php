<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle Signalement — LECTURE SEULE.
 *
 * Table `signalements` (PK char(36), UUID).
 *
 * PALIER 7.0 : uniquement des méthodes de lecture. Le traitement d'un
 * signalement (statut, `resolved_at`, `resolved_by`) est une écriture métier
 * qui sera implémentée dans un palier ultérieur, avec validation serveur et
 * journalisation dans `audit_logs`.
 *
 * `resolved_by` peut être NULL (FK ON DELETE SET NULL) → les libellés sont
 * résolus par LEFT JOIN, jamais par JOIN interne, afin de ne JAMAIS écarter
 * un signalement non traité.
 *
 * @package App\Models
 */
class Signalement extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'signalements';

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
    private const LIMITE_MAX = 100;

    /**
     * Colonnes de sélection communes (chaîne constante).
     *
     * @var string
     */
    private const COLONNES = 's.id, s.user_id, s.annonce_id, s.raison, s.status,
            s.created_at, s.resolved_at, s.resolved_by,
            a.titre AS annonce_titre,
            u.prenom AS signaleur_prenom,
            u.nom AS signaleur_nom,
            p.prenom AS auteur_prenom,
            p.nom AS auteur_nom,
            r.prenom AS resolveur_prenom,
            r.nom AS resolveur_nom';

    /**
     * Jointures : `users` (signaleur) et `annonces` sont NOT NULL (JOIN
     * interne sûr) ; `auteur` et `resolved_by` sont résolus par LEFT JOIN.
     *
     * @var string
     */
    private const DEPUIS = 'FROM signalements s
            JOIN annonces a ON a.id = s.annonce_id
            JOIN users u ON u.id = s.user_id
            LEFT JOIN users p ON p.id = a.user_id
            LEFT JOIN users r ON r.id = s.resolved_by';

    /**
     * Liste tous les signalements, les plus récents d'abord (vue administrateur).
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Signalements (tableau vide si aucun)
     */
    public function listerTous(int $limite = 100): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' ORDER BY s.created_at DESC, s.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les signalements d'un statut donné, les plus récents d'abord.
     *
     * @param string $statut Statut technique ('en_attente', 'traite', 'rejete')
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Signalements (tableau vide si aucun)
     */
    public function listerParStatut(string $statut, int $limite = 50): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE s.status = :statut'
            . ' ORDER BY s.created_at DESC, s.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les signalements portant sur une annonce donnée.
     *
     * @param string $annonceId UUID de l'annonce
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Signalements (tableau vide si aucun)
     */
    public function listerPourAnnonce(string $annonceId, int $limite = 50): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE s.annonce_id = :annonce_id'
            . ' ORDER BY s.created_at DESC, s.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':annonce_id', $annonceId, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte les signalements d'un statut donné.
     *
     * @param string $statut Statut technique
     * @return int Nombre de signalements (0 si aucun)
     */
    public function compterParStatut(string $statut): int
    {
        $sql = 'SELECT COUNT(*) FROM signalements s WHERE s.status = :statut';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte tous les signalements.
     *
     * @return int Nombre total (0 si aucun)
     */
    public function compterTous(): int
    {
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM signalements');
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