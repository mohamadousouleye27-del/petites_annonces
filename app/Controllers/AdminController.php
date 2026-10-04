<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Annonce;
use App\Models\AuditLog;
use App\Models\Categorie;
use App\Models\Message;
use App\Models\Signalement;
use App\Models\User;
use App\Models\Ville;

/**
 * Contrôleur de l'espace Administrateur.
 *
 * PÉRIMÈTRE (palier 7.3) : LECTURE SEULE sur données MariaDB RÉELLES.
 *   - les 7 écrans de cet espace sont alimentés par les modèles de lecture
 *     du palier 7.0 (User, Annonce, Signalement, AuditLog, Categorie,
 *     Ville) : plus aucune donnée statique ou fictive ;
 *   - AUCUNE requête SQL ici ni dans les vues : ce contrôleur appelle
 *     uniquement des méthodes de modèle (une requête par agrégat ou par
 *     liste, aucun N+1) ;
 *   - AUCUNE écriture en base, AUCUN formulaire, AUCUN POST : ce palier
 *     n'implémente aucun CRUD ni aucune action administrative. Les
 *     boutons d'action éventuellement présents dans les vues sont
 *     désactivés et ne déclenchent rien ;
 *   - le cloisonnement RBAC n'est PAS géré ici : les routes de cet espace
 *     sont protégées dans config/routes.php par
 *     AuthMiddleware + [RoleMiddleware::class, 'admin'] ;
 *   - le contrôleur ne lit que deux clés de session déjà renseignées à la
 *     connexion (user_prenom, user_role) pour l'affichage de l'identité.
 *
 * MODÈLES UTILISÉS (méthodes réellement existantes) :
 *   User       → compter(), compterParRole(), listerTous()
 *   Annonce    → compterToutes(), compterParStatut(), listerToutes()
 *   Signalement→ compterTous(), compterParStatut(), listerTous()
 *   AuditLog   → compterDepuis(), listerTous()
 *   Categorie  → compterToutes(), compterParStatut(), listerToutes()
 *   Ville      → compterToutes(), listerToutes()
 *
 * RESPONSABILITÉS DU RÔLE (colonnes concernées en base) :
 *   - `users` (role, status) → SEUL espace habilité à administrer les rôles
 *     et les statuts de compte ;
 *   - `annonces` (status) → vue globale, tous statuts confondus ;
 *   - `categories` (nom, parent_id, status) et `villes` (nom, type,
 *     parent_id) → référentiels de la plateforme ;
 *   - `signalements` (status, resolved_by, resolved_at) → vue globale avec
 *     traçabilité des modérateurs ;
 *   - `audit_logs` (user_id, action, description, ip_address, target_type)
 *     → journal complet de la plateforme.
 *
 * Routes associées :
 *   GET /admin               → dashboard()
 *   GET /admin/utilisateurs  → utilisateurs()
 *   GET /admin/annonces      → annonces()
 *   GET /admin/categories    → categories()
 *   GET /admin/villes        → villes()
 *   GET /admin/signalements  → signalements()
 *   GET /admin/journal       → journal()
 *
 * @package App\Controllers
 */
class AdminController extends Controller
{
    /**
     * Libellé de l'espace affiché dans la sidebar.
     *
     * @var string
     */
    private const SPACE_LABEL = 'Espace Administrateur';

    /**
     * Libellé français du rôle affiché dans la topbar.
     *
     * Volontairement constant : l'espace est réservé au rôle « admin » par
     * RoleMiddleware. App\Core\Auth::label() (étape 5) centralisera les
     * libellés pour la redirection après connexion.
     *
     * @var string
     */
    private const ROLE_LABEL = 'Administrateur';

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
     * Nombre maximal d'éléments d'un référentiel (catégories, villes).
     *
     * @var int
     */
    private const LIMITE_REFERENTIEL = 200;

    /**
     * Nombre d'entrées récentes affichées sur le tableau de bord.
     *
     * @var int
     */
    private const DERNIERES_ENTREES = 5;

    /**
     * La ligne `users` de l'administrateur connecté a-t-elle été chargée ?
     *
     * Évite une seconde requête identique au cours de la même page.
     *
     * @var bool
     */
    private bool $adminCharge = false;

    /**
     * Ligne `users` de l'administrateur connecté (null s'il est introuvable).
     *
     * @var array<string, mixed>|null
     */
    private ?array $admin = null;

    /**
     * Tableau de bord de la plateforme (GET /admin).
     *
     * Toutes les statistiques sont des agrégats réels calculés par les
     * modèles (COUNT) sur MariaDB : aucune valeur n'est codée en dur.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $donnees = $this->pageData(
            'Tableau de bord',
            "Vue d'ensemble de la plateforme"
        ) + [
            'stats'        => $this->getStats(),
            'repartition'  => $this->getRepartitionRoles(),
            'referentiels' => $this->getReferentiels(),
            'journal'      => (new AuditLog())->listerTous(self::DERNIERES_ENTREES),
        ];

        $this->viewWithLayout('admin/dashboard', $donnees);
    }

    /**
     * Administration des comptes (GET /admin/utilisateurs).
     *
     * Seul espace habilité à administrer les rôles et les statuts de compte,
     * mais LECTURE SEULE à ce palier : aucune écriture, aucun formulaire,
     * aucun POST. Le mot de passe n'est jamais sélectionné par le modèle.
     *
     * @return void
     */
    public function utilisateurs(): void
    {
        $modele = new User();

        $donnees = $this->pageData(
            'Utilisateurs',
            'Rôles, statuts et activité des comptes — lecture seule'
        ) + [
            'utilisateurs' => $modele->listerTous(self::LIMITE_LISTE),
            'total'        => $modele->compter(),
            'repartition'  => $this->getRepartitionRoles(),
        ];

        $this->viewWithLayout('admin/utilisateurs', $donnees);
    }

    /**
     * Toutes les annonces de la plateforme (GET /admin/annonces).
     *
     * @return void
     */
    public function annonces(): void
    {
        $modele = new Annonce();

        $donnees = $this->pageData(
            'Annonces',
            'Toutes les annonces, tous statuts confondus'
        ) + [
            'annonces'  => $modele->listerToutes(self::LIMITE_LISTE),
            'compteurs' => [
                'total'      => $modele->compterToutes(),
                'active'     => $modele->compterParStatut('active'),
                'en_attente' => $modele->compterParStatut('en_attente'),
                'expirée'    => $modele->compterParStatut('expirée'),
                'suspendue'  => $modele->compterParStatut('suspendue'),
            ],
        ];

        $this->viewWithLayout('admin/annonces', $donnees);
    }

    /**
     * Référentiel des catégories (GET /admin/categories).
     *
     * @return void
     */
    public function categories(): void
    {
        $modele = new Categorie();

        $donnees = $this->pageData(
            'Catégories',
            'Référentiel des catégories et sous-catégories'
        ) + [
            'categories' => $modele->listerToutes(self::LIMITE_REFERENTIEL),
            'compteurs'  => [
                'total'    => $modele->compterToutes(),
                'active'   => $modele->compterParStatut('active'),
                'inactive' => $modele->compterParStatut('inactive'),
            ],
        ];

        $this->viewWithLayout('admin/categories', $donnees);
    }

    /**
     * Référentiel des villes (GET /admin/villes).
     *
     * @return void
     */
    public function villes(): void
    {
        $modele = new Ville();

        $donnees = $this->pageData(
            'Villes',
            'Référentiel des régions, départements et communes'
        ) + [
            'villes'    => $modele->listerToutes(self::LIMITE_REFERENTIEL),
            'compteurs' => [
                'total' => $modele->compterToutes(),
            ],
        ];

        $this->viewWithLayout('admin/villes', $donnees);
    }

    /**
     * Vue globale des signalements (GET /admin/signalements).
     *
     * @return void
     */
    public function signalements(): void
    {
        $modele = new Signalement();

        $donnees = $this->pageData(
            'Signalements',
            'Vue globale et traçabilité du traitement'
        ) + [
            'signalements' => $modele->listerTous(self::LIMITE_LISTE),
            'compteurs'    => [
                'total'      => $modele->compterTous(),
                'en_attente' => $modele->compterParStatut('en_attente'),
                'traite'     => $modele->compterParStatut('traite'),
                'rejete'     => $modele->compterParStatut('rejete'),
            ],
        ];

        $this->viewWithLayout('admin/signalements', $donnees);
    }

    /**
     * Journal d'audit GLOBAL de la plateforme (GET /admin/journal).
     *
     * Contrairement au journal du modérateur (palier 7.2, filtré sur
     * l'identifiant de session du modérateur), l'administrateur consulte
     * TOUTES les entrées de `audit_logs`, tous rôles confondus.
     *
     * Le schéma réel ne comporte aucune colonne `target_id` : la cible est
     * décrite par `description` et catégorisée par `target_type`, conformément
     * au modèle AuditLog. Aucune colonne n'est inventée ici.
     *
     * LECTURE SEULE : aucune écriture dans `audit_logs`.
     *
     * @return void
     */
    public function journal(): void
    {
        $donnees = $this->pageData(
            "Journal d'audit",
            'Historique complet des actions de la plateforme'
        ) + [
            'journal' => (new AuditLog())->listerTous(self::LIMITE_LISTE),
        ];

        $this->viewWithLayout('admin/journal', $donnees);
    }

    /**
     * Profil de l'administrateur connecté (GET /admin/profil).
     *
     * LECTURE SEULE STRICTE : aucune écriture, aucun formulaire, aucun POST.
     * Le HTML est entièrement délégué au partial commun
     * app/Views/layouts/partials/profil-contenu.php, également utilisé par les
     * profils membre et modérateur (design identique pour les trois espaces).
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
        $utilisateur = $this->admin() ?? [];

        $profil = [
            'prenom'         => $this->texte($utilisateur['prenom'] ?? null),
            'nom'            => $this->texte($utilisateur['nom'] ?? null),
            'email'          => $this->texte($utilisateur['email'] ?? null),
            'telephone'      => $utilisateur['telephone'] ?? null,
            'ville'          => $utilisateur['ville_nom'] ?? null,
            // Rôle issu de la base : le libellé français est produit par
            // Auth::label() dans la vue (référentiel unique de libellés).
            'role'           => $this->texte($utilisateur['role'] ?? null),
            'statut'         => $this->texte($utilisateur['status'] ?? null),
            'avatar'         => $utilisateur['avatar'] ?? null,
            'email_verifie'  => (int) ($utilisateur['email_verified'] ?? 0),
            'created_at'     => $utilisateur['created_at'] ?? null,
        ];

        // Activité propre à l'administrateur : actions journalisées à son
        // nom (AuditLog), annonces et messages rattachés à son compte. Un
        // seul appel par modèle : aucune requête dans une boucle.
        $activite = [
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
                'label'  => 'Annonces en favori',
                'valeur' => (int) ($utilisateur['nb_favoris'] ?? 0),
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

        $this->viewWithLayout('admin/profil', $donnees);
    }

    /**
     * Données communes à toutes les pages de l'espace Administrateur.
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
        $utilisateur = $this->admin() ?? [];

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
     * Identifiant de l'utilisateur authentifié (clé de session `user_id`).
     *
     * C'est LA seule source d'identité de ce contrôleur : jamais un paramètre
     * d'URL. Une valeur absente ou non textuelle renvoie null, ce qui conduit
     * les écrans à afficher un état vide plutôt qu'une erreur ou le profil
     * d'un autre utilisateur.
     *
     * @return string|null UUID de l'administrateur connecté, ou null
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
     * Ligne `users` de l'administrateur connecté (mise en cache par requête).
     *
     * Au plus UNE lecture de l'utilisateur connecté par requête HTTP : le
     * résultat est mémorisé, y compris lorsqu'il est introuvable (évite un
     * second appel identique).
     *
     * @return array<string, mixed>|null Données du compte, ou null
     */
    private function admin(): ?array
    {
        if ($this->adminCharge) {
            return $this->admin;
        }

        $this->adminCharge = true;

        $id = $this->utilisateurId();

        if ($id === null) {
            return null;
        }

        $this->admin = (new User())->trouverParId($id);

        return $this->admin;
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
     * Entrées de la navigation principale de l'espace Administrateur.
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
                'href'  => base_path('admin'),
                'icone' => 'dashboard',
                'exact' => true,
            ],
            [
                'label' => 'Utilisateurs',
                'href'  => base_path('admin/utilisateurs'),
                'icone' => 'utilisateurs',
            ],
            [
                'label' => 'Annonces',
                'href'  => base_path('admin/annonces'),
                'icone' => 'annonces',
            ],
            [
                'label' => 'Catégories',
                'href'  => base_path('admin/categories'),
                'icone' => 'categories',
            ],
            [
                'label' => 'Villes',
                'href'  => base_path('admin/villes'),
                'icone' => 'villes',
            ],
            [
                'label' => 'Signalements',
                'href'  => base_path('admin/signalements'),
                'icone' => 'signalements',
            ],
            [
                'label' => "Journal d'audit",
                'href'  => base_path('admin/journal'),
                'icone' => 'journal',
            ],
            [
                'label' => 'Mon profil',
                'href'  => base_path('admin/profil'),
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
            ['label' => 'Mon profil',       'href' => base_path('admin/profil'),      'icone' => 'profil'],
            ['label' => 'Utilisateurs',    'href' => base_path('admin/utilisateurs'), 'icone' => 'utilisateurs'],
            ['label' => 'Catégories',      'href' => base_path('admin/categories'),   'icone' => 'categories'],
            ['label' => 'Villes',          'href' => base_path('admin/villes'),       'icone' => 'villes'],
            ['label' => "Journal d'audit", 'href' => base_path('admin/journal'),      'icone' => 'journal'],
        ];
    }

    /**
     * Cartes de statistiques du tableau de bord.
     *
     * Chaque valeur est un agrégat RÉEL calculé par un modèle sur MariaDB
     * (COUNT ou SUM) : aucune valeur n'est codée en dur, aucune n'est dérivée
     * d'une liste fictive.
     *
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        $user = new User();
        $annonce = new Annonce();
        $signalement = new Signalement();

        return [
            [
                'icone'  => 'utilisateurs',
                'valeur' => number_format($user->compter(), 0, ',', ' '),
                'label'  => 'Comptes enregistrés',
            ],
            [
                'icone'  => 'annonces',
                'valeur' => number_format($annonce->compterToutes(), 0, ',', ' '),
                'label'  => 'Annonces publiées',
            ],
            [
                'icone'  => 'signalements',
                'valeur' => number_format($signalement->compterParStatut('en_attente'), 0, ',', ' '),
                'label'  => 'Signalements en attente',
            ],
            [
                'icone'  => 'journal',
                'valeur' => number_format(
                    (new AuditLog())->compterDepuis(date('Y-m-d 00:00:00')),
                    0,
                    ',',
                    ' '
                ),
                'label'  => "Actions aujourd'hui",
            ],
        ];
    }

    /**
     * Compteurs des deux référentiels de la plateforme.
     *
     * `categories` et `villes` sont les deux seules tables de référentiel
     * réellement présentes dans le modèle de données.
     *
     * @return array<string, int>
     */
    private function getReferentiels(): array
    {
        return [
            'categories'        => (new Categorie())->compterToutes(),
            'categories_active' => (new Categorie())->compterParStatut('active'),
            'villes'            => (new Ville())->compterToutes(),
        ];
    }

    /**
     * Répartition des comptes par rôle (valeurs réelles de l'ENUM `users.role`).
     *
     * Les trois rôles sont les SEULES valeurs possibles de l'énumération :
     * 'member', 'moderateur', 'admin'. Aucune autre valeur n'est inventée et
     * aucun rôle supplémentaire n'est supposé.
     *
     * @return array<int, array{role: string, total: int}>
     */
    private function getRepartitionRoles(): array
    {
        $modele = new User();

        $repartition = [];

        foreach (['member', 'moderateur', 'admin'] as $role) {
            $repartition[] = [
                'role'  => $role,
                'total' => $modele->compterParRole($role),
            ];
        }

        return $repartition;
    }

}