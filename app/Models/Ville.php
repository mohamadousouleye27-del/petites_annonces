<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle Ville — LECTURE SEULE.
 *
 * Table `villes` : clé primaire `int(11) AUTO_INCREMENT`.
 *
 * Particularités réelles du schéma, respectées telles quelles :
 *   - `type` est un varchar(30) LIBRE (aucun ENUM, aucune contrainte) :
 *     ce modèle n'invente donc aucune liste de valeurs autorisées ;
 *   - il n'existe AUCUNE colonne `status` sur cette table : aucun filtrage
 *     par statut n'est proposé ;
 *   - `parent_id` est indexé mais SANS clé étrangère → LEFT JOIN, afin de
 *     ne jamais écarter une ville dont le rattachement est absent.
 *
 * PALIER 7.0 : lecture seule. Le CRUD des villes est une écriture métier
 * traitée ultérieurement.
 *
 * @package App\Models
 */
class Ville extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'villes';

    /**
     * Nom de la colonne de clé primaire.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Nombre maximal de lignes retournées.
     *
     * @var int
     */
    private const LIMITE_MAX = 300;

    /**
     * Liste les villes avec leur rattachement et leur nombre d'annonces.
     *
     * Le comptage est assuré par une SOUS-REQUÊTE corrélée : une seule
     * requête pour tout le référentiel (aucune requête par ligne).
     *
     * Tri déterministe : type, puis nom, puis identifiant.
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Villes (tableau vide si aucune)
     */
    public function listerToutes(int $limite = 200): array
    {
        $sql = 'SELECT v.id, v.nom, v.type, v.parent_id,
                       p.nom AS parent_nom,
                       (SELECT COUNT(*) FROM annonces a WHERE a.ville_id = v.id) AS nb_annonces,
                       (SELECT COUNT(*) FROM villes e WHERE e.parent_id = v.id) AS nb_sous_villes
                FROM villes v
                LEFT JOIN villes p ON p.id = v.parent_id
                ORDER BY v.type ASC, v.nom ASC, v.id ASC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les villes d'un type donné.
     *
     * Le type est une valeur technique transmise par l'appelant (ex:
     * 'region', 'departement') : elle est liée en paramètre, jamais
     * concaténée. Aucun contrôle de liste blanche n'est fait ici, la colonne
     * étant libre en base ; l'appelant reste responsable de la valeur qu'il
     * demande.
     *
     * @param string $type Type de division ('region', 'departement', 'commune'...)
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Villes (tableau vide si aucune)
     */
    public function listerParType(string $type, int $limite = 100): array
    {
        $sql = 'SELECT v.id, v.nom, v.type, v.parent_id,
                       p.nom AS parent_nom
                FROM villes v
                LEFT JOIN villes p ON p.id = v.parent_id
                WHERE v.type = :type
                ORDER BY v.nom ASC, v.id ASC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte toutes les villes du référentiel.
     *
     * @return int Nombre total (0 si aucune)
     */
    public function compterToutes(): int
    {
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM villes');
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