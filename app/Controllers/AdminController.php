<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * Contrôleur de l'espace Administrateur.
 *
 * PÉRIMÈTRE (étape 4) : interfaces et layout uniquement.
 *   - AUCUNE requête SQL, AUCUN modèle : les données affichées sont
 *     statiques (fictives), comme dans HomeController ;
 *     le branchement sur la base est prévu à l'étape 7 ;
 *   - le cloisonnement RBAC n'est PAS géré ici : les routes de cet espace
 *     sont protégées dans config/routes.php par
 *     AuthMiddleware + [RoleMiddleware::class, 'admin'] ;
 *   - le contrôleur ne lit que deux clés de session déjà renseignées à la
 *     connexion (user_prenom, user_role) pour l'affichage de l'identité.
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
     * Tableau de bord de la plateforme (GET /admin).
     *
     * @return void
     */
    public function dashboard(): void
    {
        $donnees = $this->pageData(
            'Tableau de bord',
            "Vue d'ensemble de la plateforme"
        ) + [
            'stats'       => $this->getStats(),
            'repartition' => $this->getRepartitionRoles(),
            'journal'     => array_slice($this->getJournal(), 0, 5),
        ];

        $this->viewWithLayout('admin/dashboard', $donnees);
    }

    /**
     * Administration des comptes (GET /admin/utilisateurs).
     *
     * Seul espace habilité à modifier les rôles et les statuts de compte.
     *
     * @return void
     */
    public function utilisateurs(): void
    {
        $donnees = $this->pageData(
            'Utilisateurs',
            'Rôles, statuts et activité des comptes'
        ) + [
            'utilisateurs' => $this->getUtilisateurs(),
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
        $annonces = $this->getAnnonces();

        $donnees = $this->pageData(
            'Annonces',
            'Toutes les annonces, tous statuts confondus'
        ) + [
            'annonces'  => $annonces,
            'compteurs' => $this->getCompteursAnnonces($annonces),
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
        $categories = $this->getCategories();

        $donnees = $this->pageData(
            'Catégories',
            'Référentiel des catégories et sous-catégories'
        ) + [
            'categories' => $categories,
            'compteurs'  => $this->getCompteursReferentiel($categories),
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
        $villes = $this->getVilles();

        $donnees = $this->pageData(
            'Villes',
            'Référentiel des régions, départements et communes'
        ) + [
            'villes'    => $villes,
            'compteurs' => $this->getCompteursReferentiel($villes),
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
        $signalements = $this->getSignalements();

        $donnees = $this->pageData(
            'Signalements',
            'Vue globale et traçabilité du traitement'
        ) + [
            'signalements' => $signalements,
            'compteurs'    => $this->getCompteursSignalements($signalements),
        ];

        $this->viewWithLayout('admin/signalements', $donnees);
    }

    /**
     * Journal d'audit complet (GET /admin/journal).
     *
     * @return void
     */
    public function journal(): void
    {
        $donnees = $this->pageData(
            "Journal d'audit",
            'Historique complet des actions de la plateforme'
        ) + [
            'journal' => $this->getJournal(),
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
     * Cartes de statistiques du tableau de bord (données fictives).
     *
     * Les compteurs « Comptes enregistrés » et « Signalements en attente »
     * sont dérivés des listes internes afin de rester cohérents ; les
     * agrégats de plateforme sont des valeurs de démonstration.
     *
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        $signalementsEnAttente = 0;

        foreach ($this->getSignalements() as $signalement) {
            if (($signalement['statut'] ?? '') === 'en_attente') {
                $signalementsEnAttente++;
            }
        }

        return [
            [
                'icone'  => 'utilisateurs',
                'valeur' => (string) count($this->getUtilisateurs()),
                'label'  => 'Comptes enregistrés',
            ],
            [
                'icone'  => 'annonces',
                'valeur' => '14 618',
                'label'  => 'Annonces publiées',
            ],
            [
                'icone'  => 'signalements',
                'valeur' => (string) $signalementsEnAttente,
                'label'  => 'Signalements en attente',
            ],
            [
                'icone'  => 'journal',
                'valeur' => '128',
                'label'  => "Actions aujourd'hui",
            ],
        ];
    }

    /**
     * Répartition des comptes par rôle, dérivée de la liste des utilisateurs.
     *
     * Les clés sont les valeurs exactes de l'ENUM `users.role` :
     * 'member', 'moderateur', 'admin'. Aucune autre valeur n'est inventée.
     *
     * @return array<int, array{role: string, total: int}>
     */
    private function getRepartitionRoles(): array
    {
        $compteurs = [
            'member'     => 0,
            'moderateur' => 0,
            'admin'      => 0,
        ];

        foreach ($this->getUtilisateurs() as $utilisateur) {
            $role = $utilisateur['role'] ?? null;

            if (is_string($role) && array_key_exists($role, $compteurs)) {
                $compteurs[$role]++;
            }
        }

        $repartition = [];

        foreach ($compteurs as $role => $total) {
            $repartition[] = [
                'role'  => $role,
                'total' => $total,
            ];
        }

        return $repartition;
    }

    /**
     * Compteurs par statut d'un référentiel (catégories, villes...).
     *
     * Un référentiel dépourvu de colonne `statut` (cas des villes) ne
     * renseigne que le total : les autres compteurs restent à zéro.
     *
     * @param array<int, array<string, mixed>> $referentiel Lignes du référentiel
     * @return array<string, int> Nombre de lignes par statut
     */
    private function getCompteursReferentiel(array $referentiel): array
    {
        $compteurs = [
            'total'    => count($referentiel),
            'active'   => 0,
            'inactive' => 0,
        ];

        foreach ($referentiel as $ligne) {
            $statut = $ligne['statut'] ?? null;

            if (is_string($statut) && array_key_exists($statut, $compteurs)) {
                $compteurs[$statut]++;
            }
        }

        return $compteurs;
    }

    /**
     * Compteurs par statut, dérivés de la liste des annonces.
     *
     * Les clés correspondent à l'ENUM `annonces.status` :
     * 'active', 'en_attente', 'expirée', 'suspendue'.
     *
     * @param array<int, array<string, mixed>> $annonces Annonces
     * @return array<string, int> Nombre d'annonces par statut
     */
    private function getCompteursAnnonces(array $annonces): array
    {
        $compteurs = [
            'total'      => count($annonces),
            'active'     => 0,
            'en_attente' => 0,
            'expirée'    => 0,
            'suspendue'  => 0,
        ];

        foreach ($annonces as $annonce) {
            $statut = $annonce['statut'] ?? null;

            if (is_string($statut) && array_key_exists($statut, $compteurs)) {
                $compteurs[$statut]++;
            }
        }

        return $compteurs;
    }

    /**
     * Compteurs par statut, dérivés de la liste des signalements.
     *
     * Les clés correspondent à l'ENUM `signalements.status` :
     * 'en_attente', 'traite', 'rejete'.
     *
     * @param array<int, array<string, mixed>> $signalements Signalements
     * @return array<string, int> Nombre de signalements par statut
     */
    private function getCompteursSignalements(array $signalements): array
    {
        $compteurs = [
            'total'      => count($signalements),
            'en_attente' => 0,
            'traite'     => 0,
            'rejete'     => 0,
        ];

        foreach ($signalements as $signalement) {
            $statut = $signalement['statut'] ?? null;

            if (is_string($statut) && array_key_exists($statut, $compteurs)) {
                $compteurs[$statut]++;
            }
        }

        return $compteurs;
    }

    /**
     * Comptes de la plateforme (données fictives).
     *
     * Colonnes alignées sur la table `users` : prenom, nom, email, role,
     * status, created_at. Le rôle est affiché tel quel ('member',
     * 'moderateur', 'admin') : aucun second référentiel de libellés.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getUtilisateurs(): array
    {
        return [
            [
                'nom'      => 'Souleymane Manga',
                'email'    => 'souleymane.manga@example.com',
                'role'     => 'admin',
                'statut'   => 'active',
                'inscrit'  => '18 octobre 2025',
                'annonces' => 0,
            ],
            [
                'nom'      => 'moussa',
                'email'    => 'moussa@example.com',
                'role'     => 'moderateur',
                'statut'   => 'active',
                'inscrit'  => '15 décembre 2025',
                'annonces' => 0,
            ],
            [
                'nom'      => 'yaya',
                'email'    => 'yaya@example.com',
                'role'     => 'member',
                'statut'   => 'active',
                'inscrit'  => '12 mars 2026',
                'annonces' => 8,
            ],
            [
                'nom'      => 'Mohamadou',
                'email'    => 'mohamadou@example.com',
                'role'     => 'member',
                'statut'   => 'active',
                'inscrit'  => '3 avril 2026',
                'annonces' => 5,
            ],
            [
                'nom'      => 'Fatou Bâ',
                'email'    => 'fatou.ba@example.com',
                'role'     => 'member',
                'statut'   => 'active',
                'inscrit'  => '9 février 2026',
                'annonces' => 3,
            ],
            [
                'nom'      => 'Awa Sow',
                'email'    => 'awa.sow@example.com',
                'role'     => 'member',
                'statut'   => 'suspendu',
                'inscrit'  => '28 janvier 2026',
                'annonces' => 14,
            ],
            [
                'nom'      => 'Ibrahima Fall',
                'email'    => 'ibrahima.fall@example.com',
                'role'     => 'member',
                'statut'   => 'banni',
                'inscrit'  => '30 novembre 2025',
                'annonces' => 21,
            ],
        ];
    }

    /**
     * Toutes les annonces de la plateforme (données fictives).
     *
     * Colonnes alignées sur la table `annonces` : titre, user_id (membre),
     * categorie_id, ville_id, prix, type_annonce, status, nb_vues,
     * created_at.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAnnonces(): array
    {
        return [
            [
                'titre'     => 'Appartement 3 pièces à Almadies',
                'membre'    => 'yaya',
                'categorie' => 'Immobilier',
                'ville'     => 'Dakar',
                'prix'      => 250000,
                'suffixe'   => '/mois',
                'statut'    => 'active',
                'vues'      => 1240,
                'date'      => 'Il y a 2 heures',
            ],
            [
                'titre'     => 'Toyota RAV4 2019 — Très bon état',
                'membre'    => 'Mohamadou',
                'categorie' => 'Véhicules',
                'ville'     => 'Dakar',
                'prix'      => 18500000,
                'suffixe'   => '',
                'statut'    => 'active',
                'vues'      => 980,
                'date'      => 'Il y a 5 heures',
            ],
            [
                'titre'     => 'Samsung Galaxy S23 Ultra 256 Go',
                'membre'    => 'Mohamadou',
                'categorie' => 'Téléphones',
                'ville'     => 'Dakar',
                'prix'      => 850000,
                'suffixe'   => '',
                'statut'    => 'en_attente',
                'vues'      => 143,
                'date'      => 'Il y a 6 heures',
            ],
            [
                'titre'     => 'MacBook Pro 14" M1 Pro',
                'membre'    => 'Fatou Bâ',
                'categorie' => 'Électronique',
                'ville'     => 'Dakar',
                'prix'      => 1250000,
                'suffixe'   => '',
                'statut'    => 'active',
                'vues'      => 1870,
                'date'      => 'Hier',
            ],
            [
                'titre'     => 'Chambre meublée à louer — Liberté 6',
                'membre'    => 'Awa Sow',
                'categorie' => 'Immobilier',
                'ville'     => 'Dakar',
                'prix'      => 75000,
                'suffixe'   => '/mois',
                'statut'    => 'expirée',
                'vues'      => 890,
                'date'      => 'Il y a 3 jours',
            ],
            [
                'titre'     => 'Terrain 300 m² à Diamniadio',
                'membre'    => 'Mohamadou',
                'categorie' => 'Immobilier',
                'ville'     => 'Rufisque',
                'prix'      => 24000000,
                'suffixe'   => '',
                'statut'    => 'en_attente',
                'vues'      => 210,
                'date'      => 'Il y a 3 jours',
            ],
            [
                'titre'     => 'Scooter électrique neuf',
                'membre'    => 'Awa Sow',
                'categorie' => 'Véhicules',
                'ville'     => 'Thiès',
                'prix'      => 650000,
                'suffixe'   => '',
                'statut'    => 'suspendue',
                'vues'      => 312,
                'date'      => 'Il y a 4 jours',
            ],
            [
                'titre'     => 'Cours particuliers de mathématiques',
                'membre'    => 'Fatou Bâ',
                'categorie' => 'Emploi & Services',
                'ville'     => 'Saint-Louis',
                'prix'      => 15000,
                'suffixe'   => '/heure',
                'statut'    => 'active',
                'vues'      => 468,
                'date'      => 'Il y a 5 jours',
            ],
        ];
    }

    /**
     * Référentiel des catégories (données fictives).
     *
     * Colonnes alignées sur la table `categories` : nom, parent_id, status.
     * `parent` vaut null pour une catégorie racine (parent_id NULL) et le nom
     * de la catégorie mère pour une sous-catégorie.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(): array
    {
        return [
            ['nom' => 'Immobilier',        'parent' => null,           'statut' => 'active',   'annonces' => 3240],
            ['nom' => 'Véhicules',         'parent' => null,           'statut' => 'active',   'annonces' => 2875],
            ['nom' => 'Électronique',      'parent' => null,           'statut' => 'active',   'annonces' => 1980],
            ['nom' => 'Téléphones',        'parent' => 'Électronique', 'statut' => 'active',   'annonces' => 1642],
            ['nom' => 'Mode',              'parent' => null,           'statut' => 'active',   'annonces' => 1250],
            ['nom' => 'Maison',            'parent' => null,           'statut' => 'active',   'annonces' => 980],
            ['nom' => 'Emploi & Services', 'parent' => null,           'statut' => 'active',   'annonces' => 1120],
            ['nom' => 'Loisirs',           'parent' => null,           'statut' => 'active',   'annonces' => 760],
            ['nom' => 'Autres',            'parent' => null,           'statut' => 'inactive', 'annonces' => 771],
        ];
    }

    /**
     * Référentiel des villes (données fictives).
     *
     * Colonnes alignées sur la table `villes` : nom, type, parent_id.
     * `parent` vaut null pour une région (parent_id NULL) et le nom de la
     * région pour un département ou une commune. La table `villes` ne possède
     * pas de colonne `statut` : aucun badge de statut n'est donc affiché.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getVilles(): array
    {
        return [
            ['nom' => 'Dakar',        'type' => 'region',      'parent' => null,    'annonces' => 5820],
            ['nom' => 'Pikine',       'type' => 'departement', 'parent' => 'Dakar', 'annonces' => 980],
            ['nom' => 'Guédiawaye',   'type' => 'departement', 'parent' => 'Dakar', 'annonces' => 640],
            ['nom' => 'Rufisque',     'type' => 'departement', 'parent' => 'Dakar', 'annonces' => 510],
            ['nom' => 'Thiès',        'type' => 'region',      'parent' => null,    'annonces' => 1420],
            ['nom' => 'Saint-Louis',  'type' => 'region',      'parent' => null,    'annonces' => 860],
            ['nom' => 'Diourbel',     'type' => 'region',      'parent' => null,    'annonces' => 540],
            ['nom' => 'Ziguinchor',   'type' => 'region',      'parent' => null,    'annonces' => 320],
        ];
    }

    /**
     * Vue globale des signalements, avec traçabilité (données fictives).
     *
     * Colonnes alignées sur la table `signalements` : annonce_id, user_id
     * (signaleur), raison, status, created_at, resolved_at, resolved_by.
     * Les statuts reprennent l'ENUM de la base : 'en_attente', 'traite',
     * 'rejete'. Les signalements non traités n'ont ni auteur de résolution
     * ni date de résolution (champs vides).
     *
     * @return array<int, array<string, string>>
     */
    private function getSignalements(): array
    {
        return [
            [
                'annonce'     => 'iPhone 14 Pro — 128 Go',
                'raison'      => 'Prix trompeur',
                'signale_par' => 'Awa Sow',
                'auteur'      => 'yaya',
                'date'        => 'Il y a 20 minutes',
                'statut'      => 'en_attente',
                'traite_par'  => '',
                'resolu_le'   => '',
            ],
            [
                'annonce'     => 'Terrain 300 m² à Diamniadio',
                'raison'      => 'Annonce en doublon',
                'signale_par' => 'Ibrahima Fall',
                'auteur'      => 'Mohamadou',
                'date'        => 'Il y a 1 heure',
                'statut'      => 'en_attente',
                'traite_par'  => '',
                'resolu_le'   => '',
            ],
            [
                'annonce'     => 'Scooter électrique neuf',
                'raison'      => 'Contenu inapproprié',
                'signale_par' => 'Fatou Bâ',
                'auteur'      => 'Awa Sow',
                'date'        => 'Il y a 3 heures',
                'statut'      => 'en_attente',
                'traite_par'  => '',
                'resolu_le'   => '',
            ],
            [
                'annonce'     => 'Machine à coudre industrielle',
                'raison'      => 'Vendeur injoignable',
                'signale_par' => 'Aminata Diop',
                'auteur'      => 'Awa Sow',
                'date'        => 'Hier',
                'statut'      => 'en_attente',
                'traite_par'  => '',
                'resolu_le'   => '',
            ],
            [
                'annonce'     => 'Climatiseur split 1,5 CV',
                'raison'      => 'Prix trompeur',
                'signale_par' => 'Moussa Ndiaye',
                'auteur'      => 'Ibrahima Fall',
                'date'        => 'Hier',
                'statut'      => 'traite',
                'traite_par'  => 'moussa',
                'resolu_le'   => "Aujourd'hui, 11 h 40",
            ],
            [
                'annonce'     => 'Ordinateur portable HP i5',
                'raison'      => 'Annonce en doublon',
                'signale_par' => 'Ousmane Diallo',
                'auteur'      => 'Fatou Bâ',
                'date'        => 'Il y a 2 jours',
                'statut'      => 'traite',
                'traite_par'  => 'moussa',
                'resolu_le'   => "Aujourd'hui, 10 h 05",
            ],
            [
                'annonce'     => 'Cours particuliers de mathématiques',
                'raison'      => 'Contenu inapproprié',
                'signale_par' => 'yaya',
                'auteur'      => 'Fatou Bâ',
                'date'        => 'Il y a 2 jours',
                'statut'      => 'rejete',
                'traite_par'  => 'moussa',
                'resolu_le'   => 'Hier, 17 h 48',
            ],
        ];
    }

    /**
     * Journal d'audit complet de la plateforme (données fictives).
     *
     * Colonnes alignées sur la table `audit_logs` : created_at, user_id,
     * action, description, ip_address, target_type.
     *
     * @return array<int, array<string, string>>
     */
    private function getJournal(): array
    {
        return [
            [
                'date'        => "Aujourd'hui, 11 h 40",
                'utilisateur' => 'moussa',
                'role'        => 'moderateur',
                'action'      => 'Annonce approuvée',
                'cible'       => 'Climatiseur split 1,5 CV',
                'type'        => 'annonce',
                'ip'          => '196.207.0.12',
            ],
            [
                'date'        => "Aujourd'hui, 10 h 05",
                'utilisateur' => 'moussa',
                'role'        => 'moderateur',
                'action'      => 'Signalement rejeté',
                'cible'       => 'Ordinateur portable HP i5',
                'type'        => 'signalement',
                'ip'          => '196.207.0.12',
            ],
            [
                'date'        => "Aujourd'hui, 09 h 12",
                'utilisateur' => 'moussa',
                'role'        => 'moderateur',
                'action'      => 'Annonce suspendue',
                'cible'       => 'Ordinateur portable HP i5',
                'type'        => 'annonce',
                'ip'          => '196.207.0.12',
            ],
            [
                'date'        => "Aujourd'hui, 08 h 30",
                'utilisateur' => 'Souleymane Manga',
                'role'        => 'admin',
                'action'      => 'Rôle modifié',
                'cible'       => 'Ousmane Diallo',
                'type'        => 'utilisateur',
                'ip'          => '196.207.0.45',
            ],
            [
                'date'        => 'Hier, 17 h 48',
                'utilisateur' => 'moussa',
                'role'        => 'moderateur',
                'action'      => 'Signalement traité',
                'cible'       => 'Climatiseur split 1,5 CV',
                'type'        => 'signalement',
                'ip'          => '196.207.0.12',
            ],
            [
                'date'        => 'Hier, 16 h 10',
                'utilisateur' => 'Souleymane Manga',
                'role'        => 'admin',
                'action'      => 'Catégorie renommée',
                'cible'       => 'Électronique',
                'type'        => 'categorie',
                'ip'          => '196.207.0.45',
            ],
            [
                'date'        => 'Hier, 15 h 22',
                'utilisateur' => 'moussa',
                'role'        => 'moderateur',
                'action'      => 'Compte consulté',
                'cible'       => 'Ibrahima Fall',
                'type'        => 'utilisateur',
                'ip'          => '196.207.0.12',
            ],
            [
                'date'        => 'Il y a 2 jours',
                'utilisateur' => 'Souleymane Manga',
                'role'        => 'admin',
                'action'      => 'Compte suspendu',
                'cible'       => 'Awa Sow',
                'type'        => 'utilisateur',
                'ip'          => '196.207.0.45',
            ],
        ];
    }








}
