<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Annonce;
use App\Models\AuditLog;
use App\Models\Message;
use App\Models\Signalement;
use App\Models\User;

/**
 * Contrôleur de l'espace Modérateur — PALIER 7.2 (LECTURE SEULE).
 *
 * Les 5 écrans de l'espace Modérateur sont désormais alimentés par les
 * données RÉELLES de la base MariaDB, via les modèles de lecture du
 * palier 7.0 :
 *
 *   GET /moderateur               → dashboard()     Signalement, Annonce, User
 *   GET /moderateur/signalements  → signalements()  Signalement
 *   GET /moderateur/annonces      → annonces()      Annonce, AuditLog
 *   GET /moderateur/utilisateurs  → utilisateurs()  User
 *   GET /moderateur/journal       → journal()       AuditLog
 *
 * RÈGLES RESPECTÉES
 *   - AUCUNE requête SQL ici ni dans les vues : tout passe par les méthodes
 *     préparées des modèles (1 appel = 1 requête, pas de N+1) ;
 *   - AUCUNE action de modération : ce palier est STRICTEMENT en lecture.
 *     Les décisions (valider / rejeter / suspendre une annonce, traiter un
 *     signalement, bannir un compte...) seront implémentées dans un palier
 *     ultérieur. Les boutons affichés sont inactifs (disabled) et ne
 *     déclenchent aucune écriture ;
 *   - le journal de /moderateur/journal est PERSONNEL : il est filtré sur
 *     l'identifiant de session `user_id` du modérateur connecté, jamais sur
 *     un identifiant fourni par l'URL ;
 *   - le cloisonnement RBAC reste assuré par les middlewares déclarés dans
 *     config/routes.php (AuthMiddleware + RoleMiddleware 'moderateur').
 *     Ce contrôleur ne prend aucune décision d'autorisation.
 *
 * @package App\Controllers
 */
class ModerateurController extends Controller
{
    /**
     * Libellé de l'espace affiché dans la sidebar.
     *
     * @var string
     */
    private const SPACE_LABEL = 'Espace Modérateur';

    /**
     * Libellé français du rôle affiché dans la topbar.
     *
     * Volontairement constant : l'espace est réservé au rôle « moderateur »
     * par RoleMiddleware, il ne peut donc afficher que ce rôle.
     *
     * @var string
     */
    private const ROLE_LABEL = 'Modérateur';

    /**
     * Nombre maximal d'éléments chargés pour une liste complète.
     *
     * Borne de présentation : les modèles appliquent eux-mêmes leur propre
     * plafond (LIMITE_MAX).
     *
     * @var int
     */
    private const LIMITE_LISTE = 100;

    /**
     * Nombre d'éléments récents affichés sur le tableau de bord.
     *
     * @var int
     */
    private const DERNIERS_ELEMENTS = 4;

    /**
     * Nombre de dernières décisions affichées sous la file d'annonces.
     *
     * @var int
     */
    private const DERNIERES_DECISIONS = 4;

    /**
     * La ligne `users` du modérateur connecté a-t-elle été chargée ?
     *
     * Évite une seconde requête identique au cours de la même page.
     *
     * @var bool
     */
    private bool $moderateurCharge = false;

    /**
     * Ligne `users` du modérateur connecté (null s'il est introuvable).
     *
     * @var array<string, mixed>|null
     */
    private ?array $moderateur = null;

    /**
     * Tableau de bord de la modération (GET /moderateur).
     *
     * Statistiques agrégées (compteurs par statut) et aperçus des files de
     * modération, tous calculés par les modèles à partir de MariaDB.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $signalements = (new Signalement())->listerTous(self::DERNIERS_ELEMENTS);
        $annonces = (new Annonce())->listerParStatut('en_attente', self::DERNIERS_ELEMENTS);

        $donnees = $this->pageData(
            'Tableau de bord',
            'File de modération et activité récente'
        ) + [
            'stats'        => $this->getStats(),
            'signalements' => $signalements,
            'annonces'     => $annonces,
        ];

        $this->viewWithLayout('moderateur/dashboard', $donnees);
    }

    /**
     * File des signalements (GET /moderateur/signalements).
     *
     * @return void
     */
    public function signalements(): void
    {
        $modele = new Signalement();

        $donnees = $this->pageData(
            'Signalements',
            'Signalements déposés par les membres'
        ) + [
            'signalements' => $modele->listerTous(self::LIMITE_LISTE),
            'compteurs'    => [
                'total'      => $modele->compterTous(),
                'en_attente' => $modele->compterParStatut('en_attente'),
                'traite'     => $modele->compterParStatut('traite'),
                'rejete'     => $modele->compterParStatut('rejete'),
            ],
        ];

        $this->viewWithLayout('moderateur/signalements', $donnees);
    }

    /**
     * Annonces en attente de décision (GET /moderateur/annonces).
     *
     * La file d'attente provient du statut réel 'en_attente' de l'ENUM
     * `annonces.status`. Les dernières décisions sont lues dans le journal
     * d'audit (`audit_logs`), la table `annonces` ne comportant aucune
     * colonne `updated_at`.
     *
     * @return void
     */
    public function annonces(): void
    {
        $modele = new Annonce();

        $donnees = $this->pageData(
            'Annonces à modérer',
            'Annonces soumises en attente de décision'
        ) + [
            'annonces' => $modele->listerParStatut('en_attente', self::LIMITE_LISTE),
            'recentes' => (new AuditLog())->listerParTypeCible('annonce', self::DERNIERES_DECISIONS),
        ];

        $this->viewWithLayout('moderateur/annonces', $donnees);
    }

    /**
     * Consultation des comptes (GET /moderateur/utilisateurs).
     *
     * Page en LECTURE SEULE : le rôle modérateur ne modifie ni les rôles ni
     * les statuts des comptes (réservé à l'espace administrateur). Le mot de
     * passe n'est jamais sélectionné par le modèle.
     *
     * @return void
     */
    public function utilisateurs(): void
    {
        $donnees = $this->pageData(
            'Utilisateurs',
            'Consultation des comptes — lecture seule'
        ) + [
            'utilisateurs' => (new User())->listerTous(self::LIMITE_LISTE),
        ];

        $this->viewWithLayout('moderateur/utilisateurs', $donnees);
    }

    /**
     * Journal personnel des actions de modération (GET /moderateur/journal).
     *
     * Le journal est filtré sur l'identifiant de session du modérateur
     * connecté : il ne voit que ses propres actions.
     *
     * @return void
     */
    public function journal(): void
    {
        $userId = $this->utilisateurId();

        $donnees = $this->pageData(
            'Mon journal',
            'Historique de vos actions de modération'
        ) + [
            'journal' => $userId !== null
                ? (new AuditLog())->listerParUtilisateur($userId, self::LIMITE_LISTE)
                : [],
        ];

        $this->viewWithLayout('moderateur/journal', $donnees);
    }

    /**
     * Profil du modérateur connecté (GET /moderateur/profil).
     *
     * LECTURE SEULE STRICTE : aucune écriture, aucun formulaire, aucun POST.
     * Le HTML est entièrement délégué au partial commun
     * app/Views/layouts/partials/profil-contenu.php, également utilisé par les
     * profils membre et administrateur (design identique pour les trois espaces).
     *
     * CLOISONNEMENT : le compte affiché est TOUJOURS l'utilisateur connecté,
     * déterminé par la clé de session `user_id`. Aucun identifiant n'est lu
     * dans l'URL, $_GET, $_POST ou $_REQUEST : il est impossible de consulter
     * le profil d'un autre compte. Le contrôle d'accès (403 pour les autres
     * rôles) reste assuré par les middlewares de config/routes.php, jamais ici.
     *
     * AUCUNE donnée sensible n'est transmise à la vue : `password_hash` n'est
     * jamais sélectionné par User::trouverParId().
     *
     * @return void
     */
    public function profil(): void
    {
        $userId = $this->utilisateurId();
        $utilisateur = $this->moderateur() ?? [];

        $profil = [
            'prenom'        => $this->texte($utilisateur['prenom'] ?? null),
            'nom'           => $this->texte($utilisateur['nom'] ?? null),
            'email'         => $this->texte($utilisateur['email'] ?? null),
            'telephone'     => $utilisateur['telephone'] ?? null,
            'ville'         => $utilisateur['ville_nom'] ?? null,
            // Rôle issu de la base : le libellé français est produit par
            // Auth::label() dans la vue (référentiel unique de libellés).
            'role'          => $this->texte($utilisateur['role'] ?? null),
            'statut'        => $this->texte($utilisateur['status'] ?? null),
            'avatar'        => $utilisateur['avatar'] ?? null,
            'email_verifie' => (int) ($utilisateur['email_verified'] ?? 0),
            'created_at'    => $utilisateur['created_at'] ?? null,
        ];

        // Activité propre au rôle de modération : décisions journalisées à
        // son nom (AuditLog) et signalements qu'il a traités (colonne réelle
        // `resolved_by`). Un seul appel par modèle : aucune requête dans une
        // boucle.
        $activite = [
            [
                'label'  => 'Signalements traités',
                'valeur' => $userId !== null
                    ? (new Signalement())->compterResolusPar($userId)
                    : 0,
            ],
            [
                'label'  => 'Actions journalisées',
                'valeur' => $userId !== null
                    ? (new AuditLog())->compterParUtilisateur($userId)
                    : 0,
            ],
            [
                'label'  => 'Annonces publiées',
                'valeur' => (int) ($utilisateur['nb_annonces'] ?? 0),
            ],
            [
                'label'  => 'Messages échangés',
                'valeur' => $userId !== null
                    ? (new Message())->compterEchanges($userId)
                    : 0,
            ],
        ];

        $donnees = $this->pageData(
            'Mon profil',
            'Vos informations personnelles et votre activité'
        ) + [
            'profil'   => $profil,
            'activite' => $activite,
        ];

        $this->viewWithLayout('moderateur/profil', $donnees);
    }

    /**
     * Données communes à toutes les pages de l'espace Modérateur.
     *
     * @param string $pageTitle Titre affiché dans le bandeau de la topbar
     * @param string $pageSubtitle Sous-titre du bandeau
     * @return array<string, mixed> Données attendues par le layout connecté
     */
    private function pageData(string $pageTitle, string $pageSubtitle): array
    {
        return [
            'pageTitle'    => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'spaceLabel'   => self::SPACE_LABEL,
            'roleLabel'    => self::ROLE_LABEL,
            'currentUser'  => $this->utilisateurCourant(),
            'menu'         => $this->menu(),
            'userLinks'    => $this->liensUtilisateur(),
        ];
    }

    /**
     * Identité de l'utilisateur connecté, pour l'affichage uniquement.
     *
     * Trois sources, dans cet ordre strict :
     *   1. la clé de session `user_id` → User::trouverParId() (données réelles
     *      de la table `users`, une seule requête mise en cache par page) ;
     *   2. le repli session (`user_prenom`) si la ligne n'a pas pu être chargée
     *      (repli de compatibilité conservé) ;
     *   3. des chaînes vides sinon, jamais de donnée inventée.
     *
     * Aucune décision d'accès n'est prise à partir de ces valeurs : le rôle
     * n'est qu'une donnée affichée, le cloisonnement reste assuré par
     * RoleMiddleware.
     *
     * @return array{prenom: string, nom: string, email: string, role: string}
     */
    private function utilisateurCourant(): array
    {
        $utilisateur = $this->moderateur() ?? [];

        $prenom = $this->texte($utilisateur['prenom'] ?? null);
        $nom    = $this->texte($utilisateur['nom'] ?? null);
        $email  = $this->texte($utilisateur['email'] ?? null);

        // Repli sur la session si la ligne n'a pas pu être chargée
        if ($prenom === '') {
            $sessionPrenom = Session::get('user_prenom');
            $prenom = is_string($sessionPrenom) ? trim($sessionPrenom) : '';
        }

        return [
            'prenom' => $prenom,
            'nom'    => $nom,
            'email'  => $email,
            'role'   => $this->currentRole(),
        ];
    }

    /**
     * Ligne `users` du modérateur connecté (mise en cache par requête).
     *
     * Au plus UNE lecture de l'utilisateur connecté par requête HTTP : le
     * résultat est mémorisé, y compris lorsqu'il est introuvable (évite un
     * second appel identique).
     *
     * @return array<string, mixed>|null Données du compte, ou null
     */
    private function moderateur(): ?array
    {
        if ($this->moderateurCharge) {
            return $this->moderateur;
        }

        $this->moderateurCharge = true;

        $id = $this->utilisateurId();

        if ($id === null) {
            return null;
        }

        $this->moderateur = (new User())->trouverParId($id);

        return $this->moderateur;
    }

    /**
     * Convertit une valeur issue de la base en chaîne d'affichage nettoyée.
     *
     * @param mixed $valeur Valeur (souvent string|null)
     * @return string Chaîne nettoyée, ou chaîne vide
     */
    private function texte(mixed $valeur): string
    {
        return is_string($valeur) ? trim($valeur) : '';
    }

    /**
     * Identifiant de l'utilisateur authentifié (clé de session `user_id`).
     *
     * C'est LA seule source d'identité du modérateur : jamais un paramètre
     * d'URL. Une valeur absente ou non textuelle renvoie null, ce qui conduit
     * le journal à afficher un état vide plutôt qu'une erreur ou le journal
     * d'un autre utilisateur.
     *
     * @return string|null UUID du modérateur connecté, ou null
     */
    private function utilisateurId(): ?string
    {
        $id = Session::get('user_id');

        if (!is_string($id)) {
            return null;
        }

        $id = trim($id);

        return $id === '' ? null : $id;
    }

    /**
     * Cartes de statistiques du tableau de bord.
     *
     * Chaque valeur est un compteur réel calculé par un modèle (agrégat
     * COUNT) : aucune donnée n'est inventée.
     *
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        $annonce = new Annonce();
        $signalement = new Signalement();

        return [
            [
                'icone'  => 'signalements',
                'valeur' => number_format($signalement->compterParStatut('en_attente'), 0, ',', ' '),
                'label'  => 'Signalements en attente',
            ],
            [
                'icone'  => 'annonces',
                'valeur' => number_format($annonce->compterParStatut('en_attente'), 0, ',', ' '),
                'label'  => 'Annonces à modérer',
            ],
            [
                'icone'  => 'valider',
                'valeur' => number_format($annonce->compterParStatut('active'), 0, ',', ' '),
                'label'  => 'Annonces publiées',
            ],
            [
                'icone'  => 'utilisateurs',
                'valeur' => number_format((new User())->compter(), 0, ',', ' '),
                'label'  => 'Utilisateurs inscrits',
            ],
        ];
    }

    /**
     * Entrées de la navigation principale de l'espace Modérateur.
     *
     * L'état actif est déduit de l'URL courante par le layout
     * (dashIsActive()) ; l'entrée du tableau de bord porte `exact` pour ne
     * pas rester active sur les sous-pages.
     *
     * @return array<int, array<string, string|bool>>
     */
    private function menu(): array
    {
        return [
            [
                'label' => 'Tableau de bord',
                'href'  => base_path('moderateur'),
                'icone' => 'dashboard',
                'exact' => true,
            ],
            [
                'label' => 'Signalements',
                'href'  => base_path('moderateur/signalements'),
                'icone' => 'signalements',
            ],
            [
                'label' => 'Annonces à modérer',
                'href'  => base_path('moderateur/annonces'),
                'icone' => 'annonces',
            ],
            [
                'label' => 'Utilisateurs',
                'href'  => base_path('moderateur/utilisateurs'),
                'icone' => 'utilisateurs',
            ],
            [
                'label' => 'Mon journal',
                'href'  => base_path('moderateur/journal'),
                'icone' => 'journal',
            ],
            [
                'label' => 'Mon profil',
                'href'  => base_path('moderateur/profil'),
                'icone' => 'profil',
            ],
        ];
    }

    /**
     * Liens du menu utilisateur (panneau déroulant de la topbar).
     *
     * @return array<int, array<string, string>>
     */
    private function liensUtilisateur(): array
    {
        return [
            ['label' => 'Mon profil',         'href' => base_path('moderateur/profil'),      'icone' => 'profil'],
            ['label' => 'Signalements',       'href' => base_path('moderateur/signalements'), 'icone' => 'signalements'],
            ['label' => 'Annonces à modérer', 'href' => base_path('moderateur/annonces'),     'icone' => 'annonces'],
            ['label' => 'Utilisateurs',       'href' => base_path('moderateur/utilisateurs'), 'icone' => 'utilisateurs'],
            ['label' => 'Mon journal',        'href' => base_path('moderateur/journal'),      'icone' => 'journal'],
        ];
    }
}
