<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Modèle Message — LECTURE SEULE.
 *
 * Table `messages` (PK char(36), UUID).
 *
 * IMPORTANT — il n'existe AUCUNE table « conversation » en base. Une
 * conversation est donc DÉRIVÉE par regroupement :
 *
 *     (annonces_id + interlocuteur)
 *
 * où l'interlocuteur est le destinataire si le membre est l'expéditeur, et
 * réciproquement. Le regroupement est réalisé par la requête elle-même
 * (CASE WHEN … GROUP BY), jamais par une boucle PHP suivie de requêtes
 * par ligne : une liste de conversations = UNE requête.
 *
 * PALIER 7.0 : lecture seule. L'envoi d'un message est une écriture métier
 * qui sera traitée ultérieurement.
 *
 * @package App\Models
 */
class Message extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'messages';

    /**
     * Nom de la colonne de clé primaire (UUID char(36)).
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Nombre maximal de conversations / messages retournés.
     *
     * @var int
     */
    private const LIMITE_MAX = 100;

    /**
     * Liste les conversations d'un membre, la plus récente d'abord.
     *
     * Une conversation = un couple (annonce, interlocuteur). Le regroupement
     * est fait par SQL (GROUP BY) : une seule requête, quel que soit le
     * nombre de conversations (aucune requête par ligne).
     *
     * L'interlocuteur est déterminé par CASE : le destinataire si le membre
     * est l'expéditeur, l'expéditeur sinon.
     *
     * Toutes les colonnes non agrégées figurent dans le GROUP BY : la requête
     * reste valide même si le serveur active ONLY_FULL_GROUP_BY.
     *
     * @param string $userId UUID du membre connecté
     * @param int $limite Nombre maximal de conversations (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Conversations (tableau vide si aucune)
     */
    public function listerConversations(string $userId, int $limite = 20): array
    {
        $sql = 'SELECT m.annonces_id,
                       a.titre AS annonce_titre,
                       i.id AS interlocuteur_id,
                       i.prenom AS interlocuteur_prenom,
                       i.nom AS interlocuteur_nom,
                       MAX(m.created_at) AS dernier_message,
                       SUM(CASE WHEN m.lu = 0 AND m.receiver_id = :user_lu THEN 1 ELSE 0 END) AS non_lus,
                       COUNT(*) AS nb_messages
                FROM messages m
                JOIN annonces a ON a.id = m.annonces_id
                JOIN users i ON i.id = CASE WHEN m.sender_id = :user_case THEN m.receiver_id ELSE m.sender_id END
                WHERE m.sender_id = :user_envoi OR m.receiver_id = :user_reception
                GROUP BY m.annonces_id, a.titre, i.id, i.prenom, i.nom
                ORDER BY dernier_message DESC, m.annonces_id DESC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_lu', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':user_case', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':user_envoi', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':user_reception', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Liste les messages d'une conversation, du plus ancien au plus récent.
     *
     * La conversation est identifiée par le triplet (annonce, membre,
     * interlocuteur) : seuls les messages échangés entre ces deux comptes au
     * sujet de cette annonce sont retournés, dans les deux sens.
     *
     * @param string $userId UUID du membre connecté
     * @param string $annonceId UUID de l'annonce concernée
     * @param string $interlocuteurId UUID de l'autre participant
     * @param int $limite Nombre maximal de messages (borné à LIMITE_MAX)
     * @return array<int, array<string, mixed>> Messages (tableau vide si aucun)
     */
    public function listerFil(
        string $userId,
        string $annonceId,
        string $interlocuteurId,
        int $limite = 50
    ): array {
        $sql = 'SELECT m.id, m.sender_id, m.receiver_id, m.annonces_id,
                       m.contenu, m.lu, m.created_at,
                       a.titre AS annonce_titre,
                       e.prenom AS expediteur_prenom,
                       e.nom AS expediteur_nom
                FROM messages m
                JOIN annonces a ON a.id = m.annonces_id
                JOIN users e ON e.id = m.sender_id
                WHERE m.annonces_id = :annonce_id
                  AND (
                        (m.sender_id = :user_a AND m.receiver_id = :interlocuteur_a)
                     OR (m.sender_id = :interlocuteur_b AND m.receiver_id = :user_b)
                      )
                ORDER BY m.created_at ASC, m.id ASC
                LIMIT :limite';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':annonce_id', $annonceId, PDO::PARAM_STR);
        $stmt->bindValue(':user_a', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':interlocuteur_a', $interlocuteurId, PDO::PARAM_STR);
        $stmt->bindValue(':interlocuteur_b', $interlocuteurId, PDO::PARAM_STR);
        $stmt->bindValue(':user_b', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $this->bornerLimite($limite), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Compte les messages non lus d'un membre (ceux qui lui sont adressés).
     *
     * @param string $userId UUID du membre
     * @return int Nombre de messages non lus (0 si aucun)
     */
    public function compterNonLus(string $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM messages m
                WHERE m.receiver_id = :user_id AND m.lu = 0';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte tous les messages auxquels un membre participe (envoyés ou reçus).
     *
     * @param string $userId UUID du membre
     * @return int Nombre de messages (0 si aucun)
     */
    public function compterEchanges(string $userId): int
    {
        $sql = 'SELECT COUNT(*) FROM messages m
                WHERE m.sender_id = :user_envoi OR m.receiver_id = :user_reception';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_envoi', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':user_reception', $userId, PDO::PARAM_STR);
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