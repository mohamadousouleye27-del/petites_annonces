<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle Categorie — LECTURE SEULE.
 *
 * Table `categories` : SEULE table métier dont la clé primaire est un
 * `int(11) AUTO_INCREMENT` (et non un UUID).
 *
 * `parent_id` est indexé mais SANS clé étrangère : les libellés du
 * rattachement sont résolus par LEFT JOIN (une sous-catégorie peut être
 * orpheline sans que sa ligne disparaisse).
 *
 * PALIER 7.0 : lecture seule. Le CRUD des catégories est une écriture
 * métier traitée ultérieurement.
 *
 * @package App\Models
 */
class Categorie extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'categories';

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
    private const LIMITE_MAX = 200;

    /**
     * Liste les catégories avec leur rattachement et leur nombre d'annonces.
     *
     * Le comptage des annonces est assuré par une SOUS-REQUÊTE corrélée :
     * une seule requête pour l'ensemble du référentiel (aucune requête par
     * ligne, donc pas de N+1).
     *
     * Tri déterministe : par nom, puis par identifiant (départage stable).
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Catégories (tableau vide si aucune)
     */
    public function listerToutes(int $limite = 100): array
    {
        $sql = 'SELECT c.id, c.nom, c.parent_id, c.status,
                       p.nom AS parent_nom,
                       (SELECT COUNT(*) FROM annonces a WHERE a.categorie_id = c.id) AS nb_annonces,
                       (SELECT COUNT(*) FROM categories e WHERE e.parent_id = c.id) AS nb_sous_categories
                FROM categories c
                LEFT JOIN categories p ON p.id = c.parent_id
                ORDER BY c.nom ASC, c.id ASC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les catégories actives, triées par nombre d'annonces.
     *
     * Réservé aux pages PUBLIQUES : seules les catégories `active` sont
     * visibles, et le compteur `nb_annonces` ne retient que les annonces
     * `active` (une annonce en attente, expirée ou suspendue ne doit pas
     * gonfler un compteur visible par un visiteur).
     *
     * Le comptage est assuré par une SOUS-REQUÊTE corrélée : une seule
     * requête pour tout le référentiel (aucune requête par ligne, donc pas
     * de N+1). Le statut est lié en paramètre, jamais concaténé.
     *
     * Tri déterministe : nombre d'annonces décroissant, puis nom, puis id.
     * Une catégorie sans annonce reste donc affichée (en fin de grille).
     *
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Catégories actives (tableau vide si aucune)
     */
    public function listerActives(int $limite = 20): array
    {
        $sql = 'SELECT c.id, c.nom, c.parent_id, c.status,
                       p.nom AS parent_nom,
                       (SELECT COUNT(*) FROM annonces a
                         WHERE a.categorie_id = c.id AND a.status = :statut_annonce) AS nb_annonces
                FROM categories c
                LEFT JOIN categories p ON p.id = c.parent_id
                WHERE c.status = :statut_categorie
                ORDER BY nb_annonces DESC, c.nom ASC, c.id ASC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut_annonce', 'active', PDO::PARAM_STR);
        $stmt->bindValue(':statut_categorie', 'active', PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte les catégories d'un statut donné.
     *
     * @param string $statut Statut technique ('active', 'inactive')
     * @return int Nombre de catégories (0 si aucune)
     */
    public function compterParStatut(string $statut): int
    {
        $sql = 'SELECT COUNT(*) FROM categories c WHERE c.status = :statut';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte toutes les catégories.
     *
     * @return int Nombre total (0 si aucune)
     */
    public function compterToutes(): int
    {
        $stmt = $this->getConnection()->prepare('SELECT COUNT(*) FROM categories');
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