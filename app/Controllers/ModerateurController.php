<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * Contrôleur de l'espace Modérateur.
 *
 * PÉRIMÈTRE (étape 3) : interfaces et layout uniquement.
 *   - AUCUNE requête SQL, AUCUN modèle : les données affichées sont
 *     statiques (fictives), comme dans HomeController ;
 *     le branchement sur la base est prévu à l'étape 7 ;
 *   - le cloisonnement RBAC n'est PAS géré ici : les routes de cet espace
 *     sont protégées dans config/routes.php par
 *     AuthMiddleware + [RoleMiddleware::class, 'moderateur'] ;
 *   - le contrôleur ne lit que deux clés de session déjà renseignées à la
 *     connexion (user_prenom, user_role) pour l'affichage de l'identité.
 *
 * RESPONSABILITÉS DU RÔLE (colonnes concernées en base) :
 *   - `signalements` (raison, status, resolved_at, resolved_by) → traiter ou
 *     rejeter les signalements ;
 *   - `annonces` (status) → approuver ou suspendre les annonces soumises ;
 *   - `users` (rôle, status) → CONSULTATION seule, sans action ;
 *   - `audit_logs` (action, description, ip_address, target_type) → journal
 *     de ses propres actions de modération.
 *
 * Routes associées :
 *   GET /moderateur               → dashboard()
 *   GET /moderateur/signalements  → signalements()
 *   GET /moderateur/annonces      → annonces()
 *   GET /moderateur/utilisateurs  → utilisateurs()
 *   GET /moderateur/journal       → journal()
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
     * App\Core\Auth::label() (étape 5) centralisera les libellés.
     *
     * @var string
     */
    private const ROLE_LABEL = 'Modérateur';

    /**
     * Tableau de bord de la modération (GET /moderateur).
     *
     * @return void
     */
    public function dashboard(): void
    {
        $signalements = $this->getSignalements();

        $donnees = $this->pageData(
            'Tableau de bord',
            'File de modération et activité récente'
        ) + [
            'stats'        => $this->getStats(),
            'signalements' => array_slice($signalements, 0, 4),
            'annonces'     => array_slice($this->getAnnoncesAModerer(), 0, 4),
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
        $signalements = $this->getSignalements();

        $donnees = $this->pageData(
            'Signalements',
            'Signalements déposés par les membres'
        ) + [
            'signalements' => $signalements,
            'compteurs'    => $this->getCompteursSignalements($signalements),
        ];

        $this->viewWithLayout('moderateur/signalements', $donnees);
    }

    /**
     * Annonces en attente de validation (GET /moderateur/annonces).
     *
     * @return void
     */
    public function annonces(): void
    {
        $donnees = $this->pageData(
            'Annonces à modérer',
            'Annonces soumises en attente de décision'
        ) + [
            'annonces'    => $this->getAnnoncesAModerer(),
            'recentes'    => $this->getAnnoncesRecentes(),
        ];

        $this->viewWithLayout('moderateur/annonces', $donnees);
    }

    /**
     * Consultation des comptes (GET /moderateur/utilisateurs).
     *
     * Page en LECTURE SEULE : le rôle modérateur ne modifie ni les rôles ni
     * les statuts des comptes (réservé à l'espace administrateur).
     *
     * @return void
     */
    public function utilisateurs(): void
    {
        $donnees = $this->pageData(
            'Utilisateurs',
            'Consultation des comptes — lecture seule'
        ) + [
            'utilisateurs' => $this->getUtilisateurs(),
        ];

        $this->viewWithLayout('moderateur/utilisateurs', $donnees);
    }

    /**
     * Journal des actions de modération (GET /moderateur/journal).
     *
     * @return void
     */
    public function journal(): void
    {
        $donnees = $this->pageData(
            'Mon journal',
            'Historique de vos actions de modération'
        ) + [
            'journal' => $this->getJournal(),
        ];

        $this->viewWithLayout('moderateur/journal', $donnees);
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
            ['label' => 'Signalements',       'href' => base_path('moderateur/signalements'), 'icone' => 'signalements'],
            ['label' => 'Annonces à modérer', 'href' => base_path('moderateur/annonces'),     'icone' => 'annonces'],
            ['label' => 'Utilisateurs',       'href' => base_path('moderateur/utilisateurs'), 'icone' => 'utilisateurs'],
            ['label' => 'Mon journal',        'href' => base_path('moderateur/journal'),      'icone' => 'journal'],
        ];
    }

    /**
     * Cartes de statistiques du tableau de bord (données fictives).
     *
     * Les deux premiers compteurs sont dérivés des données des listes afin
     * que le tableau de bord et les files restent cohérents entre eux.
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
                'icone'  => 'signalements',
                'valeur' => (string) $signalementsEnAttente,
                'label'  => 'Signalements en attente',
            ],
            [
                'icone'  => 'annonces',
                'valeur' => (string) count($this->getAnnoncesAModerer()),
                'label'  => 'Annonces à valider',
            ],
            [
                'icone'  => 'valider',
                'valeur' => '12',
                'label'  => "Décisions aujourd'hui",
            ],
            [
                'icone'  => 'journal',
                'valeur' => '38',
                'label'  => 'Actions cette semaine',
            ],
        ];
    }

    /**
     * Signalements déposés par les membres (données fictives).
     *
     * Colonnes alignées sur la table `signalements` : annonce_id, user_id
     * (signaleur), raison, status, created_at. Les statuts reprennent
     * exactement l'ENUM de la base : 'en_attente', 'traite', 'rejete'.
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
            ],
            [
                'annonce'     => 'Terrain 300 m² à Diamniadio',
                'raison'      => 'Annonce en doublon',
                'signale_par' => 'Ibrahima Fall',
                'auteur'      => 'Mohamadou',
                'date'        => 'Il y a 1 heure',
                'statut'      => 'en_attente',
            ],
            [
                'annonce'     => 'Scooter électrique neuf',
                'raison'      => 'Contenu inapproprié',
                'signale_par' => 'Fatou Bâ',
                'auteur'      => 'Ousmane Diallo',
                'date'        => 'Il y a 3 heures',
                'statut'      => 'en_attente',
            ],
            [
                'annonce'     => 'Machine à coudre industrielle',
                'raison'      => 'Vendeur injoignable',
                'signale_par' => 'Aminata Diop',
                'auteur'      => 'Awa Sow',
                'date'        => 'Hier',
                'statut'      => 'en_attente',
            ],
            [
                'annonce'     => 'Climatiseur split 1,5 CV',
                'raison'      => 'Prix trompeur',
                'signale_par' => 'Moussa Ndiaye',
                'auteur'      => 'Ibrahima Fall',
                'date'        => 'Hier',
                'statut'      => 'traite',
            ],
            [
                'annonce'     => 'Ordinateur portable HP i5',
                'raison'      => 'Annonce en doublon',
                'signale_par' => 'Ousmane Diallo',
                'auteur'      => 'Fatou Bâ',
                'date'        => 'Il y a 2 jours',
                'statut'      => 'rejete',
            ],
        ];
    }

    /**
     * Compteurs par statut, dérivés de la liste des signalements.
     *
     * Les clés correspondent exactement à l'ENUM `signalements.status` de la
     * base ('en_attente', 'traite', 'rejete').
     *
     * @param array<int, array<string, string>> $signalements Signalements
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
     * Annonces en attente de validation (données fictives).
     *
     * Colonnes alignées sur la table `annonces` : titre, user_id (membre),
     * categorie_id, type_annonce, prix, created_at, status = 'en_attente'.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAnnoncesAModerer(): array
    {
        return [
            [
                'titre'        => 'Samsung Galaxy S23 Ultra 256 Go',
                'membre'       => 'Mohamadou',
                'categorie'    => 'Téléphones',
                'type'         => 'Vente',
                'prix'         => 850000,
                'suffixe'      => '',
                'soumis'       => 'Il y a 35 minutes',
                'signalements' => 0,
            ],
            [
                'titre'        => 'Terrain 300 m² à Diamniadio',
                'membre'       => 'Mohamadou',
                'categorie'    => 'Immobilier',
                'type'         => 'Vente',
                'prix'         => 24000000,
                'suffixe'      => '',
                'soumis'       => 'Il y a 1 heure',
                'signalements' => 1,
            ],
            [
                'titre'        => 'Scooter électrique neuf',
                'membre'       => 'Ousmane Diallo',
                'categorie'    => 'Véhicules',
                'type'         => 'Vente',
                'prix'         => 650000,
                'suffixe'      => '',
                'soumis'       => 'Il y a 3 heures',
                'signalements' => 1,
            ],
            [
                'titre'        => 'Climatiseur split 1,5 CV',
                'membre'       => 'Ibrahima Fall',
                'categorie'    => 'Maison',
                'type'         => 'Vente',
                'prix'         => 285000,
                'suffixe'      => '',
                'soumis'       => 'Hier',
                'signalements' => 0,
            ],
            [
                'titre'        => 'Machine à coudre industrielle',
                'membre'       => 'Awa Sow',
                'categorie'    => 'Maison',
                'type'         => 'Vente',
                'prix'         => 320000,
                'suffixe'      => '',
                'soumis'       => 'Hier',
                'signalements' => 1,
            ],
        ];
    }

    /**
     * Annonces récemment traitées par la modération (données fictives).
     *
     * @return array<int, array<string, string>>
     */
    private function getAnnoncesRecentes(): array
    {
        return [
            [
                'titre'    => 'Climatiseur split 1,5 CV',
                'membre'   => 'Ibrahima Fall',
                'decision' => 'active',
                'date'     => "Aujourd'hui, 11 h 40",
            ],
            [
                'titre'    => 'Ordinateur portable HP i5',
                'membre'   => 'Fatou Bâ',
                'decision' => 'suspendue',
                'date'     => "Aujourd'hui, 09 h 12",
            ],
            [
                'titre'    => 'Samsung Galaxy S23 Ultra 256 Go',
                'membre'   => 'Mohamadou',
                'decision' => 'active',
                'date'     => 'Hier, 17 h 48',
            ],
            [
                'titre'    => 'Boutique commerciale à vendre',
                'membre'   => 'yaya',
                'decision' => 'expirée',
                'date'     => 'Il y a 2 jours',
            ],
        ];
    }

    /**
     * Comptes de la plateforme, en LECTURE SEULE (données fictives).
     *
     * Le rôle technique est affiché tel quel ('member', 'moderateur',
     * 'admin') : aucun second référentiel de libellés de rôle n'est créé ici.
     * App\Core\Auth::label() (étape 5) pourra fournir les libellés français
     * depuis un point unique si l'affichage le nécessite.
     *
     * Colonnes alignées sur la table `users` : prenom, nom, email, role,
     * status, created_at.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getUtilisateurs(): array
    {
        return [
            [
                'nom'      => 'Moussa Ndiaye',
                'email'    => 'moussa.ndiaye@example.com',
                'role'     => 'member',
                'statut'   => 'active',
                'inscrit'  => '12 mars 2026',
                'annonces' => 8,
            ],
            [
                'nom'      => 'Awa Sow',
                'email'    => 'awa.sow@example.com',
                'role'     => 'member',
                'statut'   => 'active',
                'inscrit'  => '3 avril 2026',
                'annonces' => 5,
            ],
            [
                'nom'      => 'Ibrahima Fall',
                'email'    => 'ibrahima.fall@example.com',
                'role'     => 'member',
                'statut'   => 'suspendu',
                'inscrit'  => '28 janvier 2026',
                'annonces' => 14,
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
                'nom'      => 'Ousmane Diallo',
                'email'    => 'ousmane.diallo@example.com',
                'role'     => 'moderateur',
                'statut'   => 'active',
                'inscrit'  => '15 décembre 2025',
                'annonces' => 0,
            ],
            [
                'nom'      => 'Aminata Diop',
                'email'    => 'aminata.diop@example.com',
                'role'     => 'member',
                'statut'   => 'banni',
                'inscrit'  => '30 novembre 2025',
                'annonces' => 21,
            ],
        ];
    }

    /**
     * Journal des propres actions de modération (données fictives).
     *
     * Colonnes alignées sur la table `audit_logs` : created_at, action,
     * description, ip_address, target_type.
     *
     * @return array<int, array<string, string>>
     */
    private function getJournal(): array
    {
        return [
            [
                'date'   => "Aujourd'hui, 11 h 40",
                'action' => 'Annonce approuvée',
                'cible'  => 'Climatiseur split 1,5 CV',
                'type'   => 'annonce',
                'ip'     => '196.207.0.12',
            ],
            [
                'date'   => "Aujourd'hui, 10 h 05",
                'action' => 'Signalement rejeté',
                'cible'  => 'Ordinateur portable HP i5',
                'type'   => 'signalement',
                'ip'     => '196.207.0.12',
            ],
            [
                'date'   => "Aujourd'hui, 09 h 12",
                'action' => 'Annonce suspendue',
                'cible'  => 'Ordinateur portable HP i5',
                'type'   => 'annonce',
                'ip'     => '196.207.0.12',
            ],
            [
                'date'   => 'Hier, 17 h 48',
                'action' => 'Signalement traité',
                'cible'  => 'Climatiseur split 1,5 CV',
                'type'   => 'signalement',
                'ip'     => '196.207.0.12',
            ],
            [
                'date'   => 'Hier, 15 h 22',
                'action' => 'Compte consulté',
                'cible'  => 'Ibrahima Fall',
                'type'   => 'utilisateur',
                'ip'     => '196.207.0.12',
            ],
            [
                'date'   => 'Il y a 2 jours',
                'action' => 'Annonce approuvée',
                'cible'  => 'Samsung Galaxy S23 Ultra 256 Go',
                'type'   => 'annonce',
                'ip'     => '196.207.0.12',
            ],
        ];
    }




}
