<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

/**
 * Contrôleur de l'espace Membre.
 *
 * PÉRIMÈTRE (étape 2) : interfaces et layout uniquement.
 *   - AUCUNE requête SQL, AUCUN modèle : les données affichées sont
 *     statiques (fictives), exactement comme dans HomeController ;
 *     le branchement sur la base est prévu à l'étape 7 ;
 *   - le cloisonnement RBAC n'est PAS géré ici : les routes de cet espace
 *     sont protégées dans config/routes.php par
 *     AuthMiddleware + [RoleMiddleware::class, 'member'] ;
 *   - le contrôleur ne lit que deux clés de session déjà renseignées à la
 *     connexion (user_prenom, user_role) pour l'affichage de l'identité.
 *
 * Routes associées :
 *   GET /membre          → dashboard()
 *   GET /membre/annonces → annonces()
 *   GET /membre/favoris  → favoris()
 *   GET /membre/messages → messages()
 *   GET /membre/profil   → profil()
 *
 * @package App\Controllers
 */
class MembreController extends Controller
{
    /**
     * Libellé de l'espace affiché dans la sidebar.
     *
     * @var string
     */
    private const SPACE_LABEL = 'Espace Membre';

    /**
     * Libellé français du rôle affiché dans la topbar.
     *
     * Volontairement constant : l'espace est réservé au rôle « member »
     * par RoleMiddleware, il ne peut donc afficher que ce rôle.
     * App\Core\Auth::label() (étape 5) centralisera les libellés pour la
     * redirection après connexion.
     *
     * @var string
     */
    private const ROLE_LABEL = 'Membre';

    /**
     * Tableau de bord du membre (GET /membre).
     *
     * @return void
     */
    public function dashboard(): void
    {
        $donnees = $this->pageData(
            'Tableau de bord',
            "Vue d'ensemble de votre activité sur PetitesAnnonces.sn"
        ) + [
            'stats'    => $this->getStats(),
            'annonces' => array_slice($this->getAnnonces(), 0, 4),
            'messages' => array_slice($this->getMessages(), 0, 3),
        ];

        $this->viewWithLayout('membre/dashboard', $donnees);
    }

    /**
     * Liste des annonces du membre (GET /membre/annonces).
     *
     * @return void
     */
    public function annonces(): void
    {
        $annonces = $this->getAnnonces();

        $donnees = $this->pageData(
            'Mes annonces',
            "Suivez l'état de publication de vos annonces"
        ) + [
            'annonces'  => $annonces,
            'compteurs' => $this->getCompteursAnnonces($annonces),
        ];

        $this->viewWithLayout('membre/annonces', $donnees);
    }

    /**
     * Annonces enregistrées en favori (GET /membre/favoris).
     *
     * @return void
     */
    public function favoris(): void
    {
        $donnees = $this->pageData(
            'Mes favoris',
            'Les annonces que vous avez enregistrées'
        ) + [
            'favoris' => $this->getFavoris(),
        ];

        $this->viewWithLayout('membre/favoris', $donnees);
    }

    /**
     * Messagerie du membre (GET /membre/messages).
     *
     * @return void
     */
    public function messages(): void
    {
        $conversations = $this->getMessages();

        $nonLus = 0;

        foreach ($conversations as $conversation) {
            if (($conversation['non_lu'] ?? false) === true) {
                $nonLus++;
            }
        }

        $donnees = $this->pageData(
            'Mes messages',
            'Vos échanges avec les autres membres'
        ) + [
            'conversations' => $conversations,
            'nonLus'        => $nonLus,
            'fil'           => $this->getFilConversation(),
        ];

        $this->viewWithLayout('membre/messages', $donnees);
    }

    /**
     * Profil du membre (GET /membre/profil).
     *
     * @return void
     */
    public function profil(): void
    {
        $utilisateur = $this->utilisateurCourant();

        $donnees = $this->pageData(
            'Mon profil',
            'Vos informations personnelles'
        ) + [
            'profil'   => $this->getProfil($utilisateur),
            'activite' => $this->getActiviteProfil(),
        ];

        $this->viewWithLayout('membre/profil', $donnees);
    }

    /**
     * Données communes à toutes les pages de l'espace Membre.
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
     * renseignées à la connexion par AuthController à partir de la table
     * `users`. Aucune requête SQL n'est exécutée ; le nom et l'email seront
     * chargés depuis la base à l'étape 7.
     *
     * Aucune décision d'accès n'est prise à partir de ces valeurs : le rôle
     * technique n'est ici qu'une donnée affichée (attribut data-role).
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
     * Entrées de la navigation principale de l'espace Membre.
     *
     * Les URL sont construites avec base_path() et l'état actif est déduit
     * de l'URL courante par le layout (dashIsActive()). L'entrée du tableau
     * de bord porte `exact` pour ne pas rester active sur les sous-pages.
     *
     * @return array<int, array<string, string|bool>>
     */
    private function menu(): array
    {
        return [
            [
                'label' => 'Tableau de bord',
                'href'  => base_path('membre'),
                'icone' => 'dashboard',
                'exact' => true,
            ],
            [
                'label' => 'Mes annonces',
                'href'  => base_path('membre/annonces'),
                'icone' => 'annonces',
            ],
            [
                'label' => 'Favoris',
                'href'  => base_path('membre/favoris'),
                'icone' => 'favoris',
            ],
            [
                'label' => 'Messages',
                'href'  => base_path('membre/messages'),
                'icone' => 'messages',
            ],
            [
                'label' => 'Mon profil',
                'href'  => base_path('membre/profil'),
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
            ['label' => 'Mon profil',   'href' => base_path('membre/profil'),   'icone' => 'profil'],
            ['label' => 'Mes annonces', 'href' => base_path('membre/annonces'), 'icone' => 'annonces'],
            ['label' => 'Mes messages', 'href' => base_path('membre/messages'), 'icone' => 'messages'],
            ['label' => 'Mes favoris',  'href' => base_path('membre/favoris'),  'icone' => 'favoris'],
        ];
    }

    /**
     * Cartes de statistiques du tableau de bord (données fictives).
     *
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        return [
            ['icone' => 'annonces', 'valeur' => '12',    'label' => 'Annonces actives'],
            ['icone' => 'oeil',     'valeur' => '4 328', 'label' => 'Vues cumulées'],
            ['icone' => 'messages', 'valeur' => '3',     'label' => 'Messages non lus'],
            ['icone' => 'favoris',  'valeur' => '7',     'label' => 'Annonces en favori'],
        ];
    }

    /**
     * Compteurs par statut, dérivés de la liste d'annonces.
     *
     * Les clés correspondent exactement aux valeurs de la colonne
     * `annonces.status` de la base ('active', 'en_attente', 'expirée',
     * 'suspendue') afin que le branchement de l'étape 7 soit immédiat.
     *
     * @param array<int, array<string, mixed>> $annonces Annonces du membre
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
     * Annonces du membre (données fictives).
     *
     * Colonnes alignées sur la table `annonces` : titre, categorie_id,
     * type_annonce, prix, status, nb_vues.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAnnonces(): array
    {
        return [
            [
                'titre'     => 'Appartement 3 pièces à Almadies',
                'categorie' => 'Immobilier',
                'type'      => 'Location',
                'prix'      => 250000,
                'suffixe'   => '/mois',
                'statut'    => 'active',
                'vues'      => 1240,
                'favoris'   => 12,
                'date'      => 'Il y a 2 heures',
            ],
            [
                'titre'     => 'Samsung Galaxy S23 Ultra 256 Go',
                'categorie' => 'Téléphones',
                'type'      => 'Vente',
                'prix'      => 850000,
                'suffixe'   => '',
                'statut'    => 'en_attente',
                'vues'      => 143,
                'favoris'   => 4,
                'date'      => 'Il y a 5 heures',
            ],
            [
                'titre'     => 'MacBook Pro 14" M1 Pro',
                'categorie' => 'Électronique',
                'type'      => 'Vente',
                'prix'      => 1250000,
                'suffixe'   => '',
                'statut'    => 'active',
                'vues'      => 1870,
                'favoris'   => 21,
                'date'      => 'Hier',
            ],
            [
                'titre'     => 'Chambre meublée à louer — Liberté 6',
                'categorie' => 'Immobilier',
                'type'      => 'Location',
                'prix'      => 75000,
                'suffixe'   => '/mois',
                'statut'    => 'expirée',
                'vues'      => 890,
                'favoris'   => 7,
                'date'      => 'Il y a 3 jours',
            ],
            [
                'titre'     => 'Téléphone Xiaomi Redmi Note 12',
                'categorie' => 'Téléphones',
                'type'      => 'Vente',
                'prix'      => 145000,
                'suffixe'   => '',
                'statut'    => 'suspendue',
                'vues'      => 312,
                'favoris'   => 2,
                'date'      => 'Il y a 4 jours',
            ],
            [
                'titre'     => 'Cours particuliers de mathématiques',
                'categorie' => 'Emploi & Services',
                'type'      => 'Vente',
                'prix'      => 15000,
                'suffixe'   => '/heure',
                'statut'    => 'active',
                'vues'      => 468,
                'favoris'   => 9,
                'date'      => 'Il y a 5 jours',
            ],
        ];
    }

    /**
     * Annonces enregistrées en favori (données fictives).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFavoris(): array
    {
        return [
            [
                'titre'        => 'Toyota RAV4 2019 — Très bon état',
                'categorie'    => 'Véhicules',
                'localisation' => 'Dakar, Plateau',
                'prix'         => 18500000,
                'suffixe'      => '',
                'date'         => 'Ajouté il y a 2 jours',
                'image'        => 'https://images.unsplash.com/photo-1550355291-bbee04a92027?w=600&q=80',
            ],
            [
                'titre'        => 'Appartement 3 pièces à Almadies',
                'categorie'    => 'Immobilier',
                'localisation' => 'Dakar, Almadies',
                'prix'         => 250000,
                'suffixe'      => '/mois',
                'date'         => 'Ajouté il y a 4 jours',
                'image'        => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=80',
            ],
            [
                'titre'        => 'MacBook Pro 14" M1 Pro',
                'categorie'    => 'Électronique',
                'localisation' => 'Dakar, Mermoz',
                'prix'         => 1250000,
                'suffixe'      => '',
                'date'         => 'Ajouté il y a 6 jours',
                'image'        => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=600&q=80',
            ],
            [
                'titre'        => 'Samsung Galaxy S23 Ultra',
                'categorie'    => 'Téléphones',
                'localisation' => 'Dakar, Ouakam',
                'prix'         => 850000,
                'suffixe'      => '',
                'date'         => 'Ajouté il y a 1 semaine',
                'image'        => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=600&q=80',
            ],
        ];
    }

    /**
     * Conversations de la messagerie (données fictives).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMessages(): array
    {
        return [
            [
                'expediteur' => 'Moussa Ndiaye',
                'sujet'      => 'Toyota RAV4 2019 — Très bon état',
                'extrait'    => 'Bonjour, le véhicule est-il toujours disponible ?',
                'date'       => 'Il y a 25 minutes',
                'non_lu'     => true,
            ],
            [
                'expediteur' => 'Awa Sow',
                'sujet'      => 'Appartement 3 pièces à Almadies',
                'extrait'    => "Je souhaiterais visiter l'appartement cette semaine.",
                'date'       => 'Il y a 3 heures',
                'non_lu'     => true,
            ],
            [
                'expediteur' => 'Ibrahima Fall',
                'sujet'      => 'MacBook Pro 14" M1 Pro',
                'extrait'    => 'Le prix est-il négociable pour un paiement immédiat ?',
                'date'       => 'Hier',
                'non_lu'     => true,
            ],
            [
                'expediteur' => 'Fatou Bâ',
                'sujet'      => 'Cours particuliers de mathématiques',
                'extrait'    => 'Merci pour votre réponse, à bientôt.',
                'date'       => 'Il y a 3 jours',
                'non_lu'     => false,
            ],
        ];
    }

    /**
     * Fil de discussion affiché dans le volet de lecture (données fictives).
     *
     * @return array<string, mixed>
     */
    private function getFilConversation(): array
    {
        return [
            'annonce'  => 'Toyota RAV4 2019 — Très bon état',
            'contact'  => 'Moussa Ndiaye',
            'messages' => [
                [
                    'auteur'  => 'contact',
                    'contenu' => 'Bonjour, le véhicule est-il toujours disponible ?',
                    'date'    => 'Il y a 40 minutes',
                ],
                [
                    'auteur'  => 'moi',
                    'contenu' => 'Bonjour, oui il est toujours disponible. Vous pouvez passer le voir au Plateau.',
                    'date'    => 'Il y a 32 minutes',
                ],
                [
                    'auteur'  => 'contact',
                    'contenu' => 'Parfait, je peux passer samedi matin vers 10 h.',
                    'date'    => 'Il y a 25 minutes',
                ],
            ],
        ];
    }

    /**
     * Informations du profil affichées dans le formulaire (démonstration).
     *
     * Le prénom provient de la session (donnée déjà disponible) ; les autres
     * champs sont des valeurs de démonstration et seront remplacés par les
     * données de la table `users` à l'étape 7.
     *
     * @param array{prenom: string, nom: string, email: string, role: string} $utilisateur Utilisateur courant
     * @return array<string, string>
     */
    private function getProfil(array $utilisateur): array
    {
        $prenom = $utilisateur['prenom'] !== '' ? $utilisateur['prenom'] : 'Membre';

        return [
            'prenom'        => $prenom,
            'nom'           => 'Diop',
            'email'         => 'membre@petitesannonces.sn',
            'telephone'     => '+221 77 123 45 67',
            'ville'         => 'Dakar',
            'membre_depuis' => 'Mars 2026',
            'statut'        => 'active',
        ];
    }

    /**
     * Chiffres d'activité affichés sur la page profil (données fictives).
     *
     * @return array<int, array<string, string>>
     */
    private function getActiviteProfil(): array
    {
        return [
            ['label' => 'Annonces publiées',  'valeur' => '12'],
            ['label' => 'Annonces en favori', 'valeur' => '7'],
            ['label' => 'Messages échangés',  'valeur' => '38'],
        ];
    }
}
