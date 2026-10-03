<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle Favori — LECTURE SEULE.
 *
 * Table `favoris` : clé primaire COMPOSITE (user_id, annonce_id), sans
 * colonne `id`. Ce modèle ne redéfinit donc pas `$primaryKey` de façon
 * utile (les méthodes génériques de App\Core\Model, qui visent `id`, ne
 * sont pas utilisées ici) : toutes les lectures passent par des requêtes
 * préparées explicites.
 *
 * PALIER 7.0 : lecture seule. L'ajout et le retrait d'un favori sont des
 * écritures métier traitées ultérieurement.
 *
 * @package App\Models
 */
class Favori extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'favoris';

    /**
     * Nombre maximal de lignes retournées.
     *
     * @var int
     */
    private const LIMITE_MAX = 100;

    /**
     * Liste les annonces mises en favori par un membre.
     *
     * Les annonces supprimées disparaissent automatiquement (FK ON DELETE
     * CASCADE sur `favoris.annonce_id`) : la jointure reste donc cohérente.
     *
     * @param string $userId UUID du membre
     * @param int $limite Nombre maximal de lignes (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Favoris (tableau vide si aucun)
     */
    public function listerPourMembre(string $userId, int $limite = 50): array
    {
        $sql = 'SELECT f.user_id, f.annonce_id, f.created_at AS ajoute_le,
                       a.titre, a.prix, a.type_annonce, a.quartier, a.status,
                       a.created_at AS annonce_le, a.nb_vues,
                       c.nom AS categorie_nom,
                       v.nom AS ville_nom,
                       u.prenom AS membre_prenom,
                       u.nom AS membre_nom
                FROM favoris f
                JOIN annonces a ON a.id = f.annonce_id
                JOIN categories c ON c.id = a.categorie_id
                JOIN villes v ON v.id = a.ville_id
                JOIN users u ON u.id = a.user_id
                WHERE f.user_id = :user_id
                ORDER BY f.created_at DESC, f.annonce_id DESC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte les annonces mises en favori par un membre.
     *
     * @param string $userId UUID du membre
     * @return int Nombre de favoris (0 si aucun)
     */
    public function compterPourMembre(string $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM favoris f WHERE f.user_id = :user_id';

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