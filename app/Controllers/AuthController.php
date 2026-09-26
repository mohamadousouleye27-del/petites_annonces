<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use PDOException;
use RuntimeException;

/**
 * Contrôleur d'authentification.
 *
 * Étapes implémentées :
 *   GET  /auth/register → register()     : affiche le formulaire d'inscription
 *   POST /auth/register → store()        : traite la création du compte
 *   GET  /auth/login    → login()        : affiche le formulaire de connexion
 *   POST /auth/login    → authenticate() : traite la connexion
 *   POST /auth/logout   → logout()       : déconnecte l'utilisateur
 *
 * Les fonctionnalités mot de passe oublié, vérification d'email, etc. ne
 * sont PAS implémentées ici (hors périmètre).
 *
 * @package App\Controllers
 */
class AuthController extends Controller
{
    /**
     * Longueur minimale du mot de passe à l'inscription.
     *
     * @var int
     */
    private const MIN_PASSWORD_LENGTH = 8;

    /**
     * Longueur maximale du prénom (colonne `prenom` VARCHAR(80)).
     *
     * @var int
     */
    private const MAX_PRENOM_LENGTH = 80;

    /**
     * Longueur maximale du nom (colonne `nom` VARCHAR(80)).
     *
     * @var int
     */
    private const MAX_NOM_LENGTH = 80;

    /**
     * Longueur maximale de l'email (colonne `email` VARCHAR(191)).
     *
     * @var int
     */
    private const MAX_EMAIL_LENGTH = 191;

    /**
     * Longueur maximale du téléphone (colonne `telephone` VARCHAR(20)).
     *
     * @var int
     */
    private const MAX_TELEPHONE_LENGTH = 20;

    /**
     * Rôle par défaut d'un nouveau membre (ENUM `role`).
     *
     * @var string
     */
    private const DEFAULT_ROLE = 'member';

    /**
     * Statut par défaut d'un nouveau compte (ENUM `status`).
     *
     * @var string
     */
    private const DEFAULT_STATUS = 'active';

    /**
     * Email vérifié ou non à l'inscription (TINYINT, 0 = non vérifié).
     *
     * @var int
     */
    private const DEFAULT_EMAIL_VERIFIED = 0;

    /**
     * Valeurs acceptées pour la case « conditions d'utilisation ».
     *
     * Un navigateur envoie « 1 » (ou « on » par défaut) lorsqu'une case
     * est cochée. Toute autre valeur (absente, tableau, chaîne vide...)
     * est refusée.
     *
     * @var array<int, string>
     */
    private const TERMS_ACCEPTED_VALUES = ['1', 'on', 'true', 'yes', 'oui'];

    /**
     * Message affiché lorsqu'un email est déjà associé à un compte.
     *
     * Volontairement générique : aucune information sur le compte
     * existant n'est révélée.
     *
     * @var string
     */
    private const DUPLICATE_EMAIL_MESSAGE = 'Cette adresse email est déjà utilisée.';

    /**
     * Message générique affiché en cas d'erreur technique inattendue.
     *
     * Aucune erreur SQL, trace ou chemin serveur n'est jamais exposé.
     *
     * @var string
     */
    private const GENERIC_ERROR_MESSAGE = 'Une erreur est survenue lors de la création de votre compte. Merci de réessayer plus tard.';

    /**
     * Message générique affiché lorsque l'email est inconnu, que le compte
     * n'est pas actif ou que le mot de passe est incorrect.
     *
     * Volontairement identique dans tous ces cas : le formulaire de
     * connexion ne révèle jamais si une adresse email correspond à un
     * compte existant.
     *
     * @var string
     */
    private const LOGIN_INVALID_CREDENTIALS_MESSAGE = 'Adresse email ou mot de passe incorrect.';

    /**
     * Message affiché lorsque le token CSRF de la connexion est absent,
     * vide, mal formé ou invalide.
     *
     * @var string
     */
    private const LOGIN_CSRF_ERROR_MESSAGE = 'Votre session a expiré ou le formulaire est invalide. Merci de réessayer.';

    /**
     * Message générique affiché en cas d'erreur technique inattendue
     * lors de la connexion.
     *
     * Aucune erreur SQL, trace ou chemin serveur n'est jamais exposé.
     *
     * @var string
     */
    private const LOGIN_GENERIC_ERROR_MESSAGE = 'Une erreur est survenue lors de la connexion. Merci de réessayer plus tard.';

    /**
     * Message générique affiché (HTTP 403) lorsque le token CSRF de la
     * déconnexion est absent, vide, mal formé ou invalide.
     *
     * Volontairement neutre : aucune information sur la session, le token
     * reçu ou le compte connecté n'est révélée. La session de l'utilisateur
     * reste intacte et l'utilisateur reste connecté.
     *
     * @var string
     */
    private const LOGOUT_CSRF_ERROR_MESSAGE = 'Votre session a expiré ou le formulaire est invalide.';

    /**
     * Modèle d'accès aux données utilisateur.
     *
     * @var User
     */
    private User $userModel;

    /**
     * Constructeur.
     *
     * @return void
     */
    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Affiche le formulaire d'inscription (GET /auth/register).
     *
     * @return void
     */
    public function register(): void
    {
        $this->renderRegisterForm();
    }

    /**
     * Traite la soumission du formulaire d'inscription (POST /auth/register).
     *
     * Enchaînement : vérification CSRF → récupération/normalisation des
     * données → validation → unicité de l'email → génération de l'UUID →
     * hashage du mot de passe → insertion en base → ouverture de session →
     * redirection vers la page d'accueil.
     *
     * En cas d'erreur, le formulaire est réaffiché (HTTP 200) avec les
     * messages d'erreur et les valeurs non sensibles saisies.
     *
     * @return void
     */
    public function store(): void
    {
        // ------------------------------------------------------------
        // 1. Protection CSRF : aucune inscription sans token valide
        // ------------------------------------------------------------
        $csrfToken = $_POST[Csrf::fieldName()] ?? null;

        if (!Csrf::validate($csrfToken)) {
            // Le token reçu n'est jamais affiché ni journalisé
            $this->renderRegisterForm(
                [],
                [],
                null,
                'Votre session a expiré ou le formulaire est invalide. Merci de réessayer.'
            );
            return;
        }

        // ------------------------------------------------------------
        // 2. Récupération et normalisation des données
        // ------------------------------------------------------------
        $prenom    = $this->textInput('prenom');
        $nom       = $this->textInput('nom');
        $email     = strtolower($this->textInput('email'));
        $telephone = $this->textInput('telephone');

        // Les mots de passe ne sont jamais trimés
        $password             = $this->rawInput('password');
        $passwordConfirmation = $this->rawInput('password_confirmation');

        // Valeurs non sensibles conservées pour un éventuel réaffichage
        $old = [
            'prenom'    => $prenom,
            'nom'       => $nom,
            'email'     => $email,
            'telephone' => $telephone,
        ];

        // ------------------------------------------------------------
        // 3. Validation des données (Validator existant uniquement)
        // ------------------------------------------------------------
        $validator = new Validator([
            'prenom'                => $prenom,
            'nom'                   => $nom,
            'email'                 => $email,
            'telephone'             => $telephone,
            'password'              => $password,
            'password_confirmation' => $passwordConfirmation,
        ]);

        $validator->setLabels([
            'prenom'    => 'prénom',
            'nom'       => 'nom',
            'email'     => 'email',
            'telephone' => 'téléphone',
            'password'  => 'mot de passe',
        ]);

        $validator->required('prenom')
            ->maxLength('prenom', self::MAX_PRENOM_LENGTH)
            ->required('nom')
            ->maxLength('nom', self::MAX_NOM_LENGTH)
            ->required('email')
            ->email('email')
            ->maxLength('email', self::MAX_EMAIL_LENGTH)
            ->required('telephone')
            ->maxLength('telephone', self::MAX_TELEPHONE_LENGTH)
            ->required('password')
            ->minLength('password', self::MIN_PASSWORD_LENGTH)
            ->confirmed('password');

        $errors = $validator->errors();

        // Validator ne possède pas de règle pour une case à cocher :
        // l'acceptation des conditions est donc vérifiée explicitement.
        if (!$this->termsAccepted()) {
            $errors['terms'] = "Vous devez accepter les conditions d'utilisation pour créer votre compte.";
        }

        if ($errors !== []) {
            $this->renderRegisterForm($old, $errors);
            return;
        }

        // ------------------------------------------------------------
        // 4. Unicité de l'email (aucun doublon autorisé)
        // ------------------------------------------------------------
        try {
            if ($this->userModel->findByEmail($email) !== null) {
                $this->renderRegisterForm($old, [
                    'email' => self::DUPLICATE_EMAIL_MESSAGE,
                ]);
                return;
            }
        } catch (PDOException | RuntimeException $e) {
            error_log('Register : échec de la vérification de l\'email (code ' . $e->getCode() . ').');
            $this->renderRegisterForm($old, [], self::GENERIC_ERROR_MESSAGE);
            return;
        }

        // ------------------------------------------------------------
        // 5. Génération de l'identifiant et hashage du mot de passe
        // ------------------------------------------------------------
        // La clé primaire `users.id` est un CHAR(36) sans AUTO_INCREMENT :
        // l'UUID doit donc être fourni explicitement par l'application.
        $id = $this->userModel->generateId();

        // Jamais de mot de passe en clair : uniquement son hash
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        if (!is_string($passwordHash) || $passwordHash === '') {
            error_log('Register : échec du hashage du mot de passe.');
            $this->renderRegisterForm($old, [], self::GENERIC_ERROR_MESSAGE);
            return;
        }

        // ------------------------------------------------------------
        // 6. Création de l'utilisateur
        // ------------------------------------------------------------
        try {
            $this->userModel->create([
                'id'             => $id,
                'prenom'         => $prenom,
                'nom'            => $nom,
                'email'          => $email,
                'password_hash'  => $passwordHash,
                'role'           => self::DEFAULT_ROLE,
                'telephone'      => $telephone,
                'ville_id'       => null,
                'avatar'         => null,
                'email_verified' => self::DEFAULT_EMAIL_VERIFIED,
                'status'         => self::DEFAULT_STATUS,
            ]);
        } catch (PDOException $e) {
            // Aucune erreur SQL brute n'est affichée à l'utilisateur
            error_log('Register : échec de la création du compte (code ' . $e->getCode() . ').');

            // Violation de la contrainte UNIQUE sur `email` (doublon détecté
            // entre la vérification d'unicité et l'insertion)
            if ($this->isDuplicateEntry($e)) {
                $this->renderRegisterForm($old, [
                    'email' => self::DUPLICATE_EMAIL_MESSAGE,
                ]);
                return;
            }

            $this->renderRegisterForm($old, [], self::GENERIC_ERROR_MESSAGE);
            return;
        } catch (RuntimeException $e) {
            // Base de données indisponible (Config\Database)
            error_log('Register : base de données indisponible lors de la création du compte.');
            $this->renderRegisterForm($old, [], self::GENERIC_ERROR_MESSAGE);
            return;
        }

        // ------------------------------------------------------------
        // 7. Ouverture de la session authentifiée
        // ------------------------------------------------------------
        // Nouvel identifiant de session (protection contre la fixation de session)
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Uniquement des informations non sensibles : ni mot de passe, ni hash
        Session::set('user_id', $id);
        Session::set('user_prenom', $prenom);
        Session::set('user_role', self::DEFAULT_ROLE);

        // ------------------------------------------------------------
        // 8. Redirection vers la page d'accueil
        // ------------------------------------------------------------
        $this->redirect(base_path('/'));
    }

    /**
     * Affiche le formulaire de connexion (GET /auth/login).
     *
     * Si un utilisateur est déjà authentifié, le formulaire est inutile :
     * il est redirigé vers la page d'accueil. Le contrôle s'appuie
     * uniquement sur le mécanisme existant de la classe de base
     * Controller::isAuthenticated() (présence de `user_id` en session) :
     * aucun middleware ni système d'authentification supplémentaire.
     *
     * @return void
     */
    public function login(): void
    {
        // Session déjà authentifiée : inutile de réafficher le formulaire
        if ($this->isAuthenticated()) {
            $this->redirect(base_path('/'));
        }

        $this->renderLoginForm();
    }

    /**
     * Traite la soumission du formulaire de connexion (POST /auth/login).
     *
     * Enchaînement : vérification CSRF → récupération/normalisation des
     * données → validation → recherche de l'utilisateur actif →
     * password_verify() → régénération de l'identifiant de session →
     * ouverture de la session authentifiée → redirection vers l'accueil.
     *
     * Toute erreur d'identifiants (email inconnu, compte non actif ou mot
     * de passe incorrect) produit le même message générique : aucune
     * information sur l'existence ou l'état du compte n'est révélée.
     *
     * En cas d'erreur, le formulaire est réaffiché (HTTP 200) avec les
     * messages d'erreur et le seul email saisi : le mot de passe n'est
     * jamais réaffiché, journalisé ni conservé en session.
     *
     * @return void
     */
    public function authenticate(): void
    {
        // ------------------------------------------------------------
        // 1. Protection CSRF : aucune connexion sans token valide
        // ------------------------------------------------------------
        $csrfToken = $_POST[Csrf::fieldName()] ?? null;

        if (!Csrf::validate($csrfToken)) {
            // Le token reçu n'est jamais affiché ni journalisé
            $this->renderLoginForm(
                [],
                [],
                null,
                self::LOGIN_CSRF_ERROR_MESSAGE
            );
            return;
        }

        // ------------------------------------------------------------
        // 2. Récupération et normalisation des données
        // ------------------------------------------------------------
        // L'email est normalisé (trim + minuscules) ; le mot de passe ne
        // doit jamais être trimé (les espaces sont significatifs).
        $email    = strtolower($this->textInput('email'));
        $password = $this->rawInput('password');

        // Seule valeur non sensible conservée pour un éventuel réaffichage
        $old = ['email' => $email];

        // ------------------------------------------------------------
        // 3. Validation des données (Validator existant uniquement)
        // ------------------------------------------------------------
        $validator = new Validator([
            'email'    => $email,
            'password' => $password,
        ]);

        $validator->setLabels([
            'email'    => 'email',
            'password' => 'mot de passe',
        ]);

        // Aucune longueur minimale n'est imposée ici : la validité du
        // couple email/mot de passe relève de password_verify(), pas de
        // la validation de forme.
        $validator->required('email')
            ->email('email')
            ->maxLength('email', self::MAX_EMAIL_LENGTH)
            ->required('password');

        $errors = $validator->errors();

        if ($errors !== []) {
            // Aucune requête de connexion ni password_verify() inutile
            $this->renderLoginForm($old, $errors);
            return;
        }

        // ------------------------------------------------------------
        // 4. Recherche de l'utilisateur actif
        // ------------------------------------------------------------
        // findActiveByEmail() restreint la recherche au statut 'active' :
        // un compte suspendu ou banni ne peut donc jamais être authentifié.
        try {
            $user = $this->userModel->findActiveByEmail($email);
        } catch (PDOException | RuntimeException $e) {
            // Aucune erreur SQL brute n'est affichée à l'utilisateur
            error_log('Login : échec de la recherche de l\'utilisateur (code ' . $e->getCode() . ').');
            $this->renderLoginForm($old, [], self::LOGIN_GENERIC_ERROR_MESSAGE);
            return;
        }

        // ------------------------------------------------------------
        // 5. Vérification du mot de passe
        // ------------------------------------------------------------
        // Un email inconnu, un compte non actif, des données de compte
        // inexploitables ou un mot de passe incorrect produisent tous le
        // même message générique (aucune fuite d'information).
        $userId = $user['id'] ?? null;
        $hash   = $user['password_hash'] ?? null;

        if ($user === null
            || !is_string($userId)
            || $userId === ''
            || !is_string($hash)
            || $hash === ''
            || !password_verify($password, $hash)
        ) {
            // Le hash et le mot de passe ne sont jamais affichés ni journalisés
            $this->renderLoginForm($old, [], self::LOGIN_INVALID_CREDENTIALS_MESSAGE);
            return;
        }

        // ------------------------------------------------------------
        // 6. Ouverture de la session authentifiée
        // ------------------------------------------------------------
        // Nouvel identifiant de session uniquement après une
        // authentification réussie (protection contre la fixation de
        // session). Les données de session, dont le token CSRF, sont
        // conservées : le système CSRF reste donc pleinement fonctionnel.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Uniquement des informations non sensibles : ni mot de passe,
        // ni hash, ni donnée inutile
        Session::set('user_id', $userId);
        Session::set(
            'user_prenom',
            is_string($user['prenom'] ?? null) ? $user['prenom'] : ''
        );
        Session::set(
            'user_role',
            is_string($user['role'] ?? null) ? $user['role'] : ''
        );

        // ------------------------------------------------------------
        // 7. Redirection vers la page d'accueil
        // ------------------------------------------------------------
        $this->redirect(base_path('/'));
    }

    /**
     * Déconnecte l'utilisateur (POST /auth/logout).
     *
     * Enchaînement : vérification CSRF → destruction de la session
     * authentifiée → création d'une nouvelle session anonyme avec un
     * nouvel identifiant de session → redirection vers la page d'accueil.
     *
     * La session n'est jamais touchée avant la validation du token CSRF :
     * une requête dépourvue de token valide laisse l'utilisateur connecté.
     *
     * Aucune requête en base de données n'est nécessaire et aucune donnée
     * sensible (mot de passe, hash, token) n'est affichée ou journalisée.
     *
     * @return void
     */
    public function logout(): void
    {
        // ------------------------------------------------------------
        // 1. Protection CSRF : aucune déconnexion sans token valide
        // ------------------------------------------------------------
        $csrfToken = $_POST[Csrf::fieldName()] ?? null;

        if (!Csrf::validate($csrfToken)) {
            // Le token reçu n'est jamais affiché ni journalisé.
            // Aucune donnée de session n'est supprimée : l'utilisateur
            // reste authentifié.
            $this->logoutForbidden();
            return;
        }

        // ------------------------------------------------------------
        // 2. Destruction de la session authentifiée
        // ------------------------------------------------------------
        // Méthode existante de App\Core\Session : vide les données
        // (user_id, user_prenom et user_role disparaissent), détruit la
        // session côté serveur et supprime le cookie de session.
        Session::destroy();

        // ------------------------------------------------------------
        // 3. Nouvelle session anonyme
        // ------------------------------------------------------------
        // Session::start() redémarre une session vide : aucune donnée
        // d'authentification de l'ancienne session ne subsiste.
        Session::start();

        // Nouvel identifiant de session : l'ancien id transmis par le
        // client n'est jamais réutilisé (session.use_strict_mode est
        // désactivé par défaut dans XAMPP, PHP accepterait sinon de
        // reprendre l'ancien id).
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // ------------------------------------------------------------
        // 4. Redirection vers la page d'accueil
        // ------------------------------------------------------------
        $this->redirect(base_path('/'));
    }

    /**
     * Prépare les données du formulaire d'inscription et affiche la vue.
     *
     * La vue ne reçoit que des données non sensibles : les mots de passe
     * ne sont jamais transmis au formulaire.
     *
     * @param array<string, string> $old Valeurs saisies à réafficher
     * @param array<string, string> $errors Erreurs de validation par champ
     * @param string|null $generalError Erreur technique générique
     * @param string|null $csrfError Erreur liée à la protection CSRF
     * @return void
     */
    private function renderRegisterForm(
        array $old = [],
        array $errors = [],
        ?string $generalError = null,
        ?string $csrfError = null
    ): void {
        $this->view('auth/register', [
            'title'        => 'Inscription — PetitesAnnonces.sn',
            'old'          => array_merge([
                'prenom'    => '',
                'nom'       => '',
                'email'     => '',
                'telephone' => '',
            ], $old),
            'errors'       => $errors,
            'generalError' => $generalError,
            'csrfError'    => $csrfError,
        ]);
    }

    /**
     * Prépare les données du formulaire de connexion et affiche la vue.
     *
     * La vue ne reçoit que des données non sensibles : le mot de passe
     * n'est jamais transmis au formulaire, seul l'email saisi est
     * conservé pour le réaffichage.
     *
     * @param array<string, string> $old Valeurs saisies à réafficher (email)
     * @param array<string, string> $errors Erreurs de validation par champ
     * @param string|null $generalError Message d'erreur non lié à un champ
     *                                   (identifiants invalides, erreur technique)
     * @param string|null $csrfError Erreur liée à la protection CSRF
     * @return void
     */
    private function renderLoginForm(
        array $old = [],
        array $errors = [],
        ?string $generalError = null,
        ?string $csrfError = null
    ): void {
        $this->view('auth/login', [
            'title'        => 'Connexion — PetitesAnnonces.sn',
            'old'          => array_merge(['email' => ''], $old),
            'errors'       => $errors,
            'generalError' => $generalError,
            'csrfError'    => $csrfError,
        ]);
    }

    /**
     * Réponse HTTP 403 renvoyée lorsque le token CSRF de la déconnexion
     * est absent ou invalide.
     *
     * Seul un message générique est affiché : ni le token reçu, ni les
     * données de session, ni une trace technique, une erreur SQL ou un
     * chemin serveur ne sont exposés. La session de l'utilisateur n'est
     * pas modifiée : l'utilisateur reste connecté.
     *
     * @return void
     */
    private function logoutForbidden(): void
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');

        // Message et URL issus de constantes : aucune donnée utilisateur
        // n'est injectée dans cette page.
        echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Requête refusée</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        h1 { color: #c0392b; font-size: 48px; margin-bottom: 10px; }
        p { color: #666; font-size: 18px; }
        a { color: #3498db; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>403</h1>
    <p>' . self::LOGOUT_CSRF_ERROR_MESSAGE . '</p>
    <p><a href="' . base_path('/') . '">Retour à l\'accueil</a></p>
</body>
</html>';
    }

    /**
     * Récupère un champ texte du formulaire et le normalise.
     *
     * Le trim n'est appliqué qu'à une valeur de type chaîne : une valeur
     * absente ou de type inattendu (tableau...) devient une chaîne vide.
     *
     * @param string $key Nom du champ POST
     * @return string Valeur nettoyée
     */
    private function textInput(string $key): string
    {
        $value = $_POST[$key] ?? null;

        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }

    /**
     * Récupère un champ POST sans transformation.
     *
     * Utilisé pour les mots de passe : ils ne doivent jamais être trimés.
     *
     * @param string $key Nom du champ POST
     * @return string Valeur brute (chaîne vide si la valeur est absente ou non textuelle)
     */
    private function rawInput(string $key): string
    {
        $value = $_POST[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /**
     * Vérifie que la case « conditions d'utilisation » a été cochée.
     *
     * Seules les valeurs réellement envoyées par un navigateur pour une
     * case cochée sont acceptées : une valeur absente, vide, ou un tableau
     * (`terms[]`) est refusé.
     *
     * @return bool True si les conditions sont acceptées
     */
    private function termsAccepted(): bool
    {
        $terms = $_POST['terms'] ?? null;

        if (!is_string($terms)) {
            return false;
        }

        return in_array($terms, self::TERMS_ACCEPTED_VALUES, true);
    }

    /**
     * Détecte une violation de contrainte d'unicité (doublon d'email).
     *
     * @param PDOException $e Exception levée par PDO
     * @return bool True s'il s'agit d'une entrée en double (SQLSTATE 23000 / errno 1062)
     */
    private function isDuplicateEntry(PDOException $e): bool
    {
        $sqlState = (string) $e->getCode();
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000'
            || $driverCode === 1062
            || $driverCode === '1062';
    }
}
