<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle Annonce — LECTURE SEULE.
 *
 * Table `annonces` (PK char(36), UUID fourni par l'application).
 *
 * PALIER 7.0 : ce modèle ne contient QUE des méthodes de lecture.
 * Aucune méthode d'écriture (create/update/delete) n'est exposée : les
 * actions métier (publier, approuver, suspendre, rejeter) seront
 * implémentées dans un palier ultérieur, avec leur validation serveur et
 * leur journalisation.
 *
 * Règles appliquées à toutes les méthodes :
 *   - requête PRÉPARÉE, aucune valeur interpolée dans le SQL ;
 *   - `LIMIT` borné par `bindValue(..., PDO::PARAM_INT)` (la connexion est
 *     configurée avec ATTR_EMULATE_PREPARES = false, donc préparation
 *     native : aucune interpolation, même pour la limite) ;
 *   - `ORDER BY` déterministe : colonne de date PUIS `id` (départage
 *     stable, y compris pour des `created_at` identiques) ;
 *   - les libellés sont résolus par JOIN (categories, villes, users) et les
 *     compteurs par SOUS-REQUÊTE corrélée : jamais une requête par ligne
 *     (pas de N+1) ;
 *   - aucune ligne trouvée → tableau vide ; identifiant inconnu → null.
 *
 * @package App\Models
 */
class Annonce extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'annonces';

    /**
     * Nom de la colonne de clé primaire (UUID char(36)).
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Nombre maximal de lignes retournées par une liste.
     *
     * Borne de sécurité : appliquée par bornage entier (max/min), jamais
     * par une valeur fournie par l'utilisateur.
     *
     * @var int
     */
    private const LIMITE_MAX = 100;

    /**
     * Colonnes de sélection communes à toutes les lectures de listes.
     *
     * Chaîne CONSTANTE (constante de classe) : elle n'est jamais construite
     * à partir d'une donnée utilisateur.
     *
     * @var string
     */
    private const COLONNES = 'a.id, a.user_id, a.categorie_id, a.titre, a.prix,
            a.type_annonce, a.ville_id, a.quartier, a.telephone, a.nb_vues,
            a.status, a.expire_at, a.created_at,
            c.nom AS categorie_nom,
            v.nom AS ville_nom,
            u.prenom AS membre_prenom,
            u.nom AS membre_nom,
            (SELECT COUNT(*) FROM favoris f WHERE f.annonce_id = a.id) AS nb_favoris,
            (SELECT COUNT(*) FROM signalements s WHERE s.annonce_id = a.id) AS nb_signalements';

    /**
     * Clause FROM commune : les libellés (catégorie, ville, membre) sont
     * résolus en une seule requête par JOIN.
     *
     * Les trois colonnes de jointure sont NOT NULL et protégées par des clés
     * étrangères : un JOIN interne ne peut donc pas écarter de ligne.
     *
     * @var string
     */
    private const DEPUIS = 'FROM annonces a
            JOIN categories c ON c.id = a.categorie_id
            JOIN villes v ON v.id = a.ville_id
            JOIN users u ON u.id = a.user_id';

    /**
     * Liste les annonces PUBLIQUES les plus récentes.
     *
     * Réservé aux pages publiques (accueil) : seules les annonces `active`
     * sont retournées — une annonce `en_attente`, `expirée` ou `suspendue`
     * ne doit jamais être visible par un visiteur. Le statut est lié en
     * paramètre, jamais concaténé dans le SQL.
     *
     * Tri déterministe : date de création décroissante, puis id (départage
     * stable pour des `created_at` identiques). Les libellés et les
     * compteurs sont résolus par la clause commune COLONNES/DEPUIS en UNE
     * seule requête (aucune requête par ligne, donc pas de N+1).
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Annonces actives (tableau vide si aucune)
     */
    public function listerActivesRecentes(int $limite = 8): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE a.status = :statut'
            . ' ORDER BY a.created_at DESC, a.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut', 'active', PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les annonces d'un membre, les plus récentes d'abord.
     *
     * @param string $userId UUID du membre propriétaire
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Annonces (tableau vide si aucune)
     */
    public function listerParMembre(string $userId, int $limite = 20): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE a.user_id = :user_id'
            . ' ORDER BY a.created_at DESC, a.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les annonces ayant un statut donné, les plus anciennes d'abord
     * (ordre d'arrivée dans la file de modération).
     *
     * @param string $statut Statut technique ('en_attente', 'active', 'expirée', 'suspendue')
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Annonces (tableau vide si aucune)
     */
    public function listerParStatut(string $statut, int $limite = 50): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' WHERE a.status = :statut'
            . ' ORDER BY a.created_at ASC, a.id ASC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste toutes les annonces de la plateforme, les plus récentes d'abord.
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Annonces (tableau vide si aucune)
     */
    public function listerToutes(int $limite = 100): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS
            . ' ORDER BY a.created_at DESC, a.id DESC'
            . ' LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Récupère une annonce par son identifiant.
     *
     * @param string $id UUID de l'annonce
     * @return array<string, mixed>|null Annonce trouvée, ou null si absente
     */
    public function trouverParId(string $id): ?array
    {
        $sql = 'SELECT ' . self::COLONNES . ' ' . self::DEPUIS . ' WHERE a.id = :id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_STR);
        $stmt->execute();

        $ligne = $stmt->fetch();

        return $ligne === false ? null : $ligne;
    }

    /**
     * Statistiques d'un membre, calculées en UNE seule requête.
     *
     * Le tableau retourné contient toujours les mêmes clés (valeurs à 0 pour
     * un membre sans aucune annonce) : aucun test supplémentaire n'est
     * nécessaire côté vue.
     *
     * @param string $userId UUID du membre
     * @return array{total: int, actives: int, en_attente: int, expirees: int, suspendues: int, vues: int}
     */
    public function statistiquesMembre(string $userId): array
    {
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(a.status = :actif), 0) AS actives,
                       COALESCE(SUM(a.status = :attente), 0) AS en_attente,
                       COALESCE(SUM(a.status = :expiree), 0) AS expirees,
                       COALESCE(SUM(a.status = :suspendue), 0) AS suspendues,
                       COALESCE(SUM(a.nb_vues), 0) AS vues
                FROM annonces a
                WHERE a.user_id = :user_id';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':actif', 'active', PDO::PARAM_STR);
        $stmt->bindValue(':attente', 'en_attente', PDO::PARAM_STR);
        $stmt->bindValue(':expiree', 'expirée', PDO::PARAM_STR);
        $stmt->bindValue(':suspendue', 'suspendue', PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        $ligne = $stmt->fetch();

        // Aucune ligne (agrégat vide) : compteurs à zéro
        if ($ligne === false) {
            return [
                'total'      => 0,
                'actives'    => 0,
                'en_attente' => 0,
                'expirees'   => 0,
                'suspendues' => 0,
                'vues'       => 0,
            ];
        }

        return [
            'total'      => (int) $ligne['total'],
            'actives'    => (int) $ligne['actives'],
            'en_attente' => (int) $ligne['en_attente'],
            'expirees'   => (int) $ligne['expirees'],
            'suspendues' => (int) $ligne['suspendues'],
            'vues'       => (int) $ligne['vues'],
        ];
    }

    /**
     * Compte les annonces d'un statut donné.
     *
     * @param string $statut Statut technique
     * @return int Nombre d'annonces (0 si aucune)
     */
    public function compterParStatut(string $statut): int
    {
        $sql = 'SELECT COUNT(*) FROM annonces a WHERE a.status = :statut';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte toutes les annonces de la plateforme.
     *
     * @return int Nombre total d'annonces (0 si aucune)
     */
    public function compterToutes(): int
    {
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM annonces');
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Borne une limite demandée entre 1 et LIMITE_MAX.
     *
     * La valeur retournée est un entier : elle est ensuite liée avec
     * PDO::PARAM_INT, jamais concaténée dans la requête.
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