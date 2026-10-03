<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Annonce;
use App\Models\AuditLog;
use App\Models\Categorie;
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
     * Seules deux clés de session sont lues : `user_prenom` et `user_role`,
     * renseignées à la connexion par AuthController. Aucune requête SQL.
     * Aucune décision d'accès n'est prise à partir de ces valeurs.
     *
     * @return array{prenom: string, nom: string, email: string, role: string}
     */
    private function utilisateurCourant(): array
    {
        $prenom = Session::get('user_prenom');

        return [
            'prenom' => is_string($prenom) ? trim($prenom) : '',
            'nom'    => '',
            'email'  => '',
            'role'   => $this->currentRole(),
        ];
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