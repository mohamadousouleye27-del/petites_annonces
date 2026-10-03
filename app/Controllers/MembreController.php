<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Annonce;
use App\Models\Favori;
use App\Models\Message;
use App\Models\User;

/**
 * Contrôleur de l'espace Membre — PALIER 7.1 (LECTURE SEULE).
 *
 * Les 5 écrans de l'espace Membre sont alimentés par les données RÉELLES de
 * la base MariaDB, via les modèles de lecture du palier 7.0 :
 *
 *   GET /membre          → dashboard()   Annonce, Favori, Message
 *   GET /membre/annonces → annonces()    Annonce
 *   GET /membre/favoris  → favoris()     Favori
 *   GET /membre/messages → messages()    Message
 *   GET /membre/profil   → profil()      User, Message
 *
 * RÈGLES RESPECTÉES
 *   - AUCUNE requête SQL ici ni dans les vues : tout passe par les méthodes
 *     préparées des modèles (1 appel = 1 requête, pas de N+1) ;
 *   - AUCUN CRUD : ce palier est strictement en lecture ; les écritures
 *     (publier, modifier, retirer un favori, envoyer un message...) seront
 *     traitées dans un palier ultérieur ;
 *   - CLOISONNEMENT : le propriétaire des données est TOUJOURS l'utilisateur
 *     authentifié (clé de session `user_id`). Aucun identifiant fourni par
 *     l'URL n'est utilisé pour déterminer le propriétaire ;
 *   - le contrôle d'accès (rôles) reste assuré par les middlewares déclarés
 *     dans config/routes.php (AuthMiddleware + RoleMiddleware). Ce
 *     contrôleur ne prend aucune décision d'autorisation.
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
     * @var string
     */
    private const ROLE_LABEL = 'Membre';

    /**
     * Nombre de dernières annonces affichées sur le tableau de bord.
     *
     * @var int
     */
    private const DERNIERES_ANNONCES = 4;

    /**
     * Nombre de conversations récentes affichées sur le tableau de bord.
     *
     * @var int
     */
    private const DERNIERES_CONVERSATIONS = 3;

    /**
     * Nombre maximal d'éléments chargés pour une liste complète.
     *
     * Borne de présentation uniquement : les modèles appliquent eux-mêmes
     * leur propre plafond (LIMITE_MAX).
     *
     * @var int
     */
    private const LIMITE_LISTE = 100;

    /**
     * L'utilisateur connecté a-t-il déjà été chargé depuis la base ?
     *
     * Évite une seconde requête identique au cours de la même page.
     *
     * @var bool
     */
    private bool $membreCharge = false;

    /**
     * Ligne `users` de l'utilisateur connecté (null s'il est introuvable).
     *
     * @var array<string, mixed>|null
     */
    private ?array $membre = null;

    /**
     * Tableau de bord du membre (GET /membre).
     *
     * Toutes les données sont calculées pour l'utilisateur CONNECTÉ :
     * statistiques d'annonces (1 requête agrégée), dernières annonces,
     * conversations récentes, compteurs de messages non lus et de favoris.
     *
     * @return void
     */
    public function dashboard(): void
    {
        $userId = $this->utilisateurId();

        $modeleAnnonce = new Annonce();
        $modeleMessage = new Message();

        $statistiques = $userId !== null
            ? $modeleAnnonce->statistiquesMembre($userId)
            : $this->statistiquesVides();

        $annonces = $userId !== null
            ? $modeleAnnonce->listerParMembre($userId, self::DERNIERES_ANNONCES)
            : [];

        $conversations = $userId !== null
            ? $modeleMessage->listerConversations($userId, self::DERNIERES_CONVERSATIONS)
            : [];

        $nonLus  = $userId !== null ? $modeleMessage->compterNonLus($userId) : 0;
        $favoris = $userId !== null ? (new Favori())->compterPourMembre($userId) : 0;

        $donnees = $this->pageData(
            'Tableau de bord',
            "Vue d'ensemble de votre activité sur PetitesAnnonces.sn"
        ) + [
            'stats' => [
                [
                    'icone'  => 'annonces',
                    'valeur' => number_format($statistiques['actives'], 0, ',', ' '),
                    'label'  => 'Annonces actives',
                ],
                [
                    'icone'  => 'oeil',
                    'valeur' => number_format($statistiques['vues'], 0, ',', ' '),
                    'label'  => 'Vues cumulées',
                ],
                [
                    'icone'  => 'messages',
                    'valeur' => number_format($nonLus, 0, ',', ' '),
                    'label'  => 'Messages non lus',
                ],
                [
                    'icone'  => 'favoris',
                    'valeur' => number_format($favoris, 0, ',', ' '),
                    'label'  => 'Annonces en favori',
                ],
            ],
            'annonces'      => $annonces,
            'conversations' => $conversations,
            'nonLus'        => $nonLus,
        ];

        $this->viewWithLayout('membre/dashboard', $donnees);
    }

    /**
     * Liste des annonces du membre connecté (GET /membre/annonces).
     *
     * @return void
     */
    public function annonces(): void
    {
        $userId = $this->utilisateurId();
        $modele = new Annonce();

        $annonces = $userId !== null
            ? $modele->listerParMembre($userId, self::LIMITE_LISTE)
            : [];

        $compteurs = $userId !== null
            ? $modele->statistiquesMembre($userId)
            : $this->statistiquesVides();

        $donnees = $this->pageData(
            'Mes annonces',
            "Suivez l'état de publication de vos annonces"
        ) + [
            'annonces'  => $annonces,
            'compteurs' => $compteurs,
        ];

        $this->viewWithLayout('membre/annonces', $donnees);
    }

    /**
     * Annonces enregistrées en favori par le membre connecté
     * (GET /membre/favoris).
     *
     * @return void
     */
    public function favoris(): void
    {
        $userId = $this->utilisateurId();
        $modele = new Favori();

        $favoris = $userId !== null
            ? $modele->listerPourMembre($userId, self::LIMITE_LISTE)
            : [];

        $total = $userId !== null ? $modele->compterPourMembre($userId) : 0;

        $donnees = $this->pageData(
            'Mes favoris',
            'Les annonces que vous avez enregistrées'
        ) + [
            'favoris' => $favoris,
            'total'   => $total,
        ];

        $this->viewWithLayout('membre/favoris', $donnees);
    }

    /**
     * Messagerie du membre connecté (GET /membre/messages).
     *
     * LECTURE SEULE : la conversation affichée dans le volet de lecture est
     * choisie par les paramètres d'URL `annonce` et `interlocuteur`, mais
     * UNIQUEMENT si ce couple correspond à une conversation réelle du membre
     * (retournée par listerConversations()). Un identifiant inconnu ou
     * appartenant à un autre membre retombe sur la conversation la plus
     * récente. Aucun de ces paramètres ne sert à déterminer la propriété des
     * données : celle-ci reste fixée par `user_id` de session.
     *
     * @return void
     */
    public function messages(): void
    {
        $userId = $this->utilisateurId();
        $modele = new Message();

        $conversations = $userId !== null
            ? $modele->listerConversations($userId)
            : [];

        $nonLus = $userId !== null ? $modele->compterNonLus($userId) : 0;

        $conversation = $this->conversationOuverte($conversations);

        $lignes = ($userId !== null && $conversation !== null)
            ? $modele->listerFil(
                $userId,
                (string) $conversation['annonces_id'],
                (string) $conversation['interlocuteur_id']
            )
            : [];

        $donnees = $this->pageData(
            'Mes messages',
            'Vos échanges avec les autres membres'
        ) + [
            'conversations'       => $conversations,
            'nonLus'              => $nonLus,
            'fil'                 => $this->construireFil($lignes, $userId, $conversation),
            'conversationOuverte' => $conversation,
        ];

        $this->viewWithLayout('membre/messages', $donnees);
    }

    /**
     * Profil du membre connecté (GET /membre/profil).
     *
     * Les informations proviennent de la table `users` (User::trouverParId),
     * y compris ville et compteurs. Les valeurs absentes (NULL en base) sont
     * transmises brutes : la vue les remplace par « — ». LECTURE SEULE :
     * aucune modification de profil n'est proposée.
     *
     * @return void
     */
    public function profil(): void
    {
        $userId = $this->utilisateurId();
        $utilisateur = $this->membre() ?? [];

        $profil = [
            'prenom'     => $this->texte($utilisateur['prenom'] ?? null),
            'nom'        => $this->texte($utilisateur['nom'] ?? null),
            'email'      => $this->texte($utilisateur['email'] ?? null),
            'telephone'  => $utilisateur['telephone'] ?? null,
            'ville'      => $utilisateur['ville_nom'] ?? null,
            'created_at' => $utilisateur['created_at'] ?? null,
            'statut'     => $this->texte($utilisateur['status'] ?? null),
        ];

        $activite = [
            ['label' => 'Annonces publiées',  'valeur' => (int) ($utilisateur['nb_annonces'] ?? 0)],
            ['label' => 'Annonces en favori', 'valeur' => (int) ($utilisateur['nb_favoris'] ?? 0)],
            [
                'label'  => 'Messages échangés',
                'valeur' => $userId !== null ? (new Message())->compterEchanges($userId) : 0,
            ],
        ];

        $donnees = $this->pageData(
            'Mon profil',
            'Vos informations personnelles et votre activité'
        ) + [
            'profil'   => $profil,
            'activite' => $activite,
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
     * Identité de l'utilisateur connecté, pour l'affichage (topbar).
     *
     * Le prénom, le nom et l'email proviennent de la table `users`
     * (User::trouverParId, une seule requête mise en cache). Le rôle
     * technique provient de la session. Aucune décision d'accès n'est prise
     * ici : le rôle n'est qu'une donnée affichée.
     *
     * @return array{prenom: string, nom: string, email: string, role: string}
     */
    private function utilisateurCourant(): array
    {
        $utilisateur = $this->membre() ?? [];

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
     * Entrées de la navigation principale de l'espace Membre.
     *
     * Les URL sont construites avec base_path() ; l'état actif est déduit de
     * l'URL courante par le layout (dashIsActive()). L'entrée du tableau de
     * bord porte `exact` pour ne pas rester active sur les sous-pages.
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
     * Identifiant de l'utilisateur authentifié (clé de session `user_id`).
     *
     * C'est LA seule source de propriété des données : jamais un paramètre
     * d'URL. Une valeur absente ou non textuelle renvoie null, ce qui conduit
     * les écrans à afficher un état vide plutôt qu'une erreur.
     *
     * @return string|null UUID du membre connecté, ou null
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
     * Ligne `users` de l'utilisateur connecté (mise en cache par requête).
     *
     * @return array<string, mixed>|null Données du compte, ou null
     */
    private function membre(): ?array
    {
        if ($this->membreCharge) {
            return $this->membre;
        }

        $this->membreCharge = true;

        $id = $this->utilisateurId();

        if ($id === null) {
            return null;
        }

        $this->membre = (new User())->trouverParId($id);

        return $this->membre;
    }

    /**
     * Sélectionne la conversation à afficher dans le volet de lecture.
     *
     * Le couple (annonce, interlocuteur) fourni en URL n'est accepté que s'il
     * correspond à une conversation RÉELLE du membre connecté. Dans tous les
     * autres cas (aucun paramètre, identifiants inconnus ou appartenant à un
     * autre membre), la conversation la plus récente est retenue.
     *
     * @param array<int, array<string, mixed>> $conversations Conversations du membre
     * @return array<string, mixed>|null Conversation ouverte, ou null si aucune
     */
    private function conversationOuverte(array $conversations): ?array
    {
        if ($conversations === []) {
            return null;
        }

        $annonce = $this->getInput('annonce');
        $interlocuteur = $this->getInput('interlocuteur');

        if (is_string($annonce) && is_string($interlocuteur)) {
            $annonce = trim($annonce);
            $interlocuteur = trim($interlocuteur);

            if ($annonce !== '' && $interlocuteur !== '') {
                foreach ($conversations as $conversation) {
                    if ((string) $conversation['annonces_id'] === $annonce
                        && (string) $conversation['interlocuteur_id'] === $interlocuteur
                    ) {
                        return $conversation;
                    }
                }
            }
        }

        return $conversations[0];
    }

    /**
     * Normalise un fil de discussion pour la vue.
     *
     * Chaque message est marqué « moi » (envoyé par le membre) ou « contact »
     * (reçu). Les dates restent brutes : c'est la vue qui les met en forme
     * (dashTempsRelatif).
     *
     * @param array<int, array<string, mixed>> $lignes Messages du fil
     * @param string|null $userId UUID du membre connecté
     * @param array<string, mixed>|null $conversation Conversation ouverte
     * @return array{annonce: string, contact: string, messages: array<int, array<string, mixed>>}
     */
    private function construireFil(array $lignes, ?string $userId, ?array $conversation): array
    {
        $messages = [];

        foreach ($lignes as $ligne) {
            $expediteur = (string) ($ligne['sender_id'] ?? '');

            $messages[] = [
                'auteur'  => ($userId !== null && $expediteur === $userId) ? 'moi' : 'contact',
                'contenu' => (string) ($ligne['contenu'] ?? ''),
                'date'    => $ligne['created_at'] ?? null,
                'lu'      => (int) ($ligne['lu'] ?? 0) === 1,
            ];
        }

        if ($conversation !== null) {
            $contact = trim(
                (string) ($conversation['interlocuteur_prenom'] ?? '')
                . ' '
                . (string) ($conversation['interlocuteur_nom'] ?? '')
            );

            $annonce = (string) ($conversation['annonce_titre'] ?? '');
        } else {
            $contact = '';
            $annonce = '';
        }

        return [
            'annonce'  => $annonce,
            'contact'  => $contact,
            'messages' => $messages,
        ];
    }

    /**
     * Statistiques d'annonces à zéro (repli si l'utilisateur est introuvable).
     *
     * @return array{total: int, actives: int, en_attente: int, expirees: int, suspendues: int, vues: int}
     */
    private function statistiquesVides(): array
    {
        return [
            'total'      => 0,
            'actives'    => 0,
            'en_attente' => 0,
            'expirees'   => 0,
            'suspendues' => 0,
            'vues'       => 0,
        ];
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
}