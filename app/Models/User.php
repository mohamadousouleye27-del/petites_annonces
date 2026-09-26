<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Modèle User
 *
 * Représente l'accès aux données de la table `users`.
 *
 * Ce modèle est uniquement responsable de l'accès aux données utilisateur
 * (lecture/écriture en base). Il ne gère PAS le workflow d'authentification :
 * ni le hashage du mot de passe (fait explicitement dans Register avec
 * password_hash()), ni sa vérification (faite explicitement dans Login avec
 * password_verify()), ni la session utilisateur.
 *
 * Particularité : la clé primaire `id` est un CHAR(36) (UUID) sans
 * AUTO_INCREMENT. Ce modèle fournit donc une méthode de génération d'UUID v4
 * cryptographiquement sûre, à utiliser plus tard lors de la création d'un
 * utilisateur (étape Register).
 *
 * @package App\Models
 */
class User extends Model
{
    /**
     * Nom de la table associée au modèle.
     *
     * @var string
     */
    protected string $table = 'users';

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
     * Les colonnes `created_at` et `updated_at` sont volontairement
     * exclues car elles sont gérées automatiquement par la base
     * (DEFAULT CURRENT_TIMESTAMP).
     *
     * @var array<int, string>
     */
    protected array $fillable = [
        // La clé primaire est un UUID fourni par l'application
        // (generateId()) : sans cette entrée, filterFillable() la
        // supprimerait et l'INSERT échouerait, la colonne n'ayant
        // pas d'AUTO_INCREMENT.
        'id',
        'prenom',
        'nom',
        'email',
        'password_hash',
        'role',
        'telephone',
        'ville_id',
        'avatar',
        'email_verified',
        'status',
    ];

    /**
     * Génère un UUID v4 cryptographiquement sûr.
     *
     * Utilise random_bytes() (source aléatoire cryptographiquement sûre
     * fournie par PHP) plutôt que uniqid(). Positionne correctement les
     * bits de version (4) et de variant (RFC 4122) afin de produire un
     * identifiant au format :
     *   xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     *
     * @return string UUID v4 généré
     */
    protected function generateUuidV4(): string
    {
        // 16 octets aléatoires issus d'une source cryptographiquement sûre
        $data = random_bytes(16);

        // Positionne la version sur 4 (octet 7, bits de poids fort à 0100)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

        // Positionne le variant RFC 4122 (octet 9, bits de poids fort à 10)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        // Formatage en chaîne standard 8-4-4-4-12
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Fournit un nouvel identifiant UUID v4 pour un futur enregistrement.
     *
     * Cette méthode publique expose la génération d'UUID afin qu'un
     * futur contrôleur (Register) puisse l'utiliser pour renseigner la
     * clé primaire `id` avant l'insertion, la table ne disposant pas
     * d'AUTO_INCREMENT.
     *
     * @return string Nouvel UUID v4
     */
    public function generateId(): string
    {
        return $this->generateUuidV4();
    }

    /**
     * Recherche un utilisateur par son adresse email.
     *
     * Utilise une requête préparée pour éviter toute injection SQL.
     *
     * @param string $email Adresse email recherchée
     * @return array<string, mixed>|null Données de l'utilisateur ou null si absent
     */
    public function findByEmail(string $email): ?array
    {
        // Requête préparée avec un placeholder nommé
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':email' => $email]);

        // Retourne l'enregistrement trouvé ou null s'il n'existe pas
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Recherche un utilisateur actif par son adresse email.
     *
     * Identique à findByEmail() mais restreint la recherche aux comptes
     * ayant le statut 'active'. Utile pour la future authentification
     * (Login), afin d'éviter qu'un compte suspendu ou banni puisse être
     * authentifié.
     *
     * @param string $email Adresse email recherchée
     * @return array<string, mixed>|null Données de l'utilisateur actif ou null si absent
     */
    public function findActiveByEmail(string $email): ?array
    {
        // Requête préparée avec des placeholders nommés
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND status = :status";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':status' => 'active',
        ]);

        // Retourne l'enregistrement trouvé ou null s'il n'existe pas
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }
}
