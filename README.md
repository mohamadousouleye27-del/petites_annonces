# PetitesAnnonces.sn

Plateforme web de petites annonces destinée au Sénégal.

- **Nom du projet** : PetitesAnnonces.sn
- **Objectif** : mettre en relation acheteurs, vendeurs et prestataires autour d'annonces classées par catégorie et par localisation.
- **Type d'application** : application web dynamique, côté serveur.
- **Architecture** : PHP MVC maison (contrôleurs, modèles, vues).
- **Base de données** : MariaDB / MySQL (InnoDB, `utf8mb4`).

> **État réel du projet.** Ce README décrit **uniquement ce qui est actuellement implémenté dans le code**. Les fonctionnalités prévues mais pas encore codées sont listées explicitement en section [8. Ce qui n'est PAS encore implémenté](#8-ce-qui-nest-pas-encore-implémenté).

### Sommaire

1. [Présentation](#1-présentation)
2. [Fonctionnalités actuellement disponibles](#2-fonctionnalités-actuellement-disponibles)
3. [Architecture](#3-architecture)
4. [Installation](#4-installation)
5. [Configuration](#5-configuration)
6. [Base de données](#6-base-de-données)
7. [Routage, middlewares et sécurité](#7-routage-middlewares-et-sécurité)
8. [Ce qui n'est PAS encore implémenté](#8-ce-qui-nest-pas-encore-implémenté)
9. [Outils de contrôle](#9-outils-de-contrôle)
10. [État Git](#10-état-git)

---

## 1. Présentation

PetitesAnnonces.sn est une application web de petites annonces pensée pour le marché sénégalais (ville de référence : Dakar, fuseau `Africa/Dakar`, montants en FCFA).

Elle est développée en PHP sans framework : le socle MVC (`App\Core\Router`, `App\Core\Controller`, `App\Core\Model`, `App\Core\Database`, …) est écrit maison, et l'autoloading est assuré par Composer (PSR-4).

Le projet est **actuellement dans une phase de lecture seule sur données réelles** : l'authentification écrit réellement en base (création de compte), tandis que tous les espaces métier (membre, modérateur, administrateur) et la page d'accueil se contentent de **lire** et d'afficher les données de MariaDB. Aucun CRUD métier n'est encore codé.

### Stack technique

| Élément | Valeur |
|---|---|
| Langage | PHP >= 8.2 (`declare(strict_types=1)` partout) |
| Architecture | MVC maison, POO |
| Base de données | MariaDB / MySQL via PDO |
| Autoloading | Composer, PSR-4 (`App\` → `app/`, `App\Core\` → `core/`) |
| Front-end | TailwindCSS (CDN) + CSS/JS maison (`public/assets/`) |
| Serveur | Apache (XAMPP) avec réécriture d'URL |
| Dépendances externes | aucune (pas de package Composer tiers) |

---

## 2. Fonctionnalités actuellement disponibles

### Visiteur (public)

Seule la **page d'accueil** (`GET /`) est réellement accessible et fonctionnelle. Elle affiche :

- un **hero** avec une barre de recherche ;
- les **statistiques réelles** de la plateforme : nombre d'annonces au statut `active`, nombre de comptes enregistrés, nombre de régions couvertes (compteurs `COUNT` calculés par les modèles, jamais codés en dur) ;
- la grille **« Parcourir par catégorie »** : jusqu'à 10 catégories `active`, triées par nombre d'annonces ;
- la grille **« Annonces récentes »** : jusqu'à 8 annonces `active`, avec titre, prix formaté en FCFA, catégorie, ville, quartier et nombre de vues ;
- les **liens de navigation et le pied de page**.

> ⚠️ **À noter honnêtement** : la barre de recherche et plusieurs liens de navigation pointent vers `/annonces` et `/annonces/creer`. **Ces routes ne sont pas encore déclarées** et renvoient donc une erreur **404** (vérifié). Ce sont des liens de design, pas des fonctionnalités.

Il n'existe **pas encore** de page de consultation d'une annonce, de page de catégories, ni de moteur de recherche fonctionnel.

### Authentification

Implémentée et fonctionnelle dans `app/Controllers/AuthController.php`.

| Route | Contrôleur | Fonctionnement |
|---|---|---|
| `GET /auth/login` | `login()` | ✅ Formulaire de connexion |
| `POST /auth/login` | `authenticate()` | ✅ Connexion réelle |
| `GET /auth/register` | `register()` | ✅ Formulaire d'inscription |
| `POST /auth/register` | `store()` | ✅ Création réelle du compte en base |
| `POST /auth/logout` | `logout()` | ✅ Déconnexion |

Ce que fait réellement le code :

- **Inscription** : validation serveur via `App\Core\Validator` (prénom, nom, email, mot de passe, confirmation, conditions acceptées), doublon email détecté sur `SQLSTATE 23000` / errno `1062`, insertion dans `users` avec rôle `member`, statut `active`, `email_verified = 0`.
- **Mots de passe** : `password_hash()` / `password_verify()` avec `PASSWORD_DEFAULT` (bcrypt). Le hash n'est jamais renvoyé ni affiché.
- **Clé primaire UUID** : `User::generateId()` produit un UUID v4 à partir de `random_bytes()` (la table `users` n'a pas d'`AUTO_INCREMENT`).
- **CSRF** : chaque POST est protégé par un jeton `App\Core\Csrf` (généré par `random_bytes()`, comparé avec `hash_equals()`). Un jeton invalide sur `/auth/logout` renvoie une page **403** dédiée.
- **Session** : `session_regenerate_id(true)` après inscription et après connexion (protection contre la fixation de session). Clés écrites : `user_id`, `user_prenom`, `user_role`.
- **Messages d'erreur** : volontairement génériques (aucune fuite d'information sur l'existence d'un compte, aucune trace SQL exposée).

> Non implémenté : mot de passe oublié, réinitialisation, vérification d'adresse e-mail, « se souvenir de moi ».

### Membre — `/membre`

Cloisonnement strict : `AuthMiddleware` + `RoleMiddleware('member')`. Un visiteur anonyme est redirigé vers `/auth/login` ; un utilisateur ayant un autre rôle reçoit un **403**.

Toutes ces pages sont **alimentées par les données réelles du membre connecté** (le propriétaire est toujours déterminé par la clé de session `user_id`, jamais par un identifiant fourni dans l'URL). Elles sont **en lecture seule**.

| URL | Contenu réellement affiché |
|---|---|
| `GET /membre` | Statistiques de ses annonces (total, actives, en attente, expirées, suspendues, vues cumulées), dernières annonces, conversations récentes, compteurs de messages non lus et de favoris |
| `GET /membre/annonces` | Liste de ses annonces avec statut, prix, vues, favoris et signalements |
| `GET /membre/favoris` | Liste de ses annonces mises en favori |
| `GET /membre/messages` | Liste de ses conversations (regroupées par annonce + interlocuteur) et fil de discussion de la conversation ouverte |
| `GET /membre/profil` | Ses informations de compte (sans le hash), sa ville, son nombre d'annonces et de favoris, et son nombre de messages |

> Les conversations sont **dérivées** : il n'existe pas de table `conversation` en base. Le regroupement (annonce + interlocuteur) est fait directement en SQL (`GROUP BY`), en une seule requête.
>
> Aucun bouton d'action n'est fonctionnel : publier, modifier, retirer un favori ou envoyer un message n'est pas implémenté.

### Modérateur — `/moderateur`

Cloisonnement strict : `AuthMiddleware` + `RoleMiddleware('moderateur')`. **Lecture seule stricte** : aucune action de modération n'est codée, les boutons affichés sont `disabled` avec l'indication « Action disponible dans une prochaine étape ».

| URL | Contenu réellement affiché |
|---|---|
| `GET /moderateur` | Compteurs réels (signalements en attente, annonces à modérer, annonces publiées, utilisateurs inscrits) + files récentes |
| `GET /moderateur/signalements` | Tous les signalements, les plus récents d'abord, avec annonce, signaleur et auteur |
| `GET /moderateur/annonces` | Annonces du statut `en_attente` (file de modération) + dernières décisions (journal d'audit) |
| `GET /moderateur/utilisateurs` | Liste des comptes (avec leur rôle et statut) |
| `GET /moderateur/journal` | **Journal personnel** : uniquement les entrées d'audit du modérateur connecté |

### Administrateur — `/admin`

Cloisonnement strict : `AuthMiddleware` + `RoleMiddleware('admin')`. **Lecture seule** : aucun formulaire, aucun POST, aucune écriture. Les boutons d'action sont `disabled` (« Non fonctionnel à ce stade (lecture seule) »).

| URL | Contenu réellement affiché |
|---|---|
| `GET /admin` | Compteurs globaux (comptes enregistrés, annonces, signalements en attente, actions du jour) + répartition des comptes par rôle + compteurs des référentiels |
| `GET /admin/utilisateurs` | Tous les comptes, tous rôles confondus |
| `GET /admin/annonces` | Toutes les annonces, tous statuts confondus |
| `GET /admin/categories` | Référentiel des catégories avec parent et nombre d'annonces |
| `GET /admin/villes` | Référentiel des villes avec parent et nombre d'annonces |
| `GET /admin/signalements` | Tous les signalements avec traçabilité du modérateur ayant traité |
| `GET /admin/journal` | **Journal d'audit global** de la plateforme |

### Récapitulatif

| Espace | Écrans | Écritures en base |
|---|---|---|
| Public (accueil) | 1 | aucune |
| Authentification | 2 | **oui** (création de compte uniquement) |
| Membre | 5 | aucune |
| Modérateur | 5 | aucune |
| Administrateur | 7 | aucune |

---

## 3. Architecture

### Arborescence réelle

```text
petites_annonces/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php        Accueil public (lecture)
│   │   ├── AuthController.php        Inscription / connexion / déconnexion
│   │   ├── MembreController.php      Espace membre (lecture)
│   │   ├── ModerateurController.php  Espace modérateur (lecture)
│   │   └── AdminController.php       Espace administrateur (lecture)
│   ├── Middleware/
│   │   ├── Middleware.php            Classe abstraite + helpers
│   │   ├── AuthMiddleware.php        Redirige vers /auth/login
│   │   ├── GuestMiddleware.php       Redirige vers l'accueil
│   │   └── RoleMiddleware.php        403 si rôle non autorisé
│   ├── Models/
│   │   ├── User.php                  users (lecture + création)
│   │   ├── Annonce.php               annonces (lecture seule)
│   │   ├── Categorie.php             categories (lecture seule)
│   │   ├── Ville.php                 villes (lecture seule)
│   │   ├── Favori.php                favoris (lecture seule)
│   │   ├── Message.php               messages (lecture seule)
│   │   ├── Signalement.php           signalements (lecture seule)
│   │   └── AuditLog.php              audit_logs (lecture seule)
│   └── Views/
│       ├── admin/                    7 vues
│       ├── auth/                     login.php, register.php
│       ├── home/                     index.php
│       ├── layouts/
│       │   ├── connected.php         Layout des 3 espaces connectés
│       │   └── partials/
│       │       └── connected-helpers.php  Icônes, formats, badges
│       ├── membre/                   5 vues
│       ├── moderateur/               5 vues
│       ├── annonces/                 (vide — routes non implémentées)
│       ├── favoris/                  (vide — routes non implémentées)
│       ├── messages/                 (vide — routes non implémentées)
│       └── profil/                   (vide — routes non implémentées)
│
├── core/                             Socle MVC
│   ├── Env.php                       Lecteur .env maison (sans dépendance)
│   ├── Router.php                    Routage + exécution des middlewares
│   ├── Controller.php                view(), viewWithLayout(), redirect()
│   ├── Model.php                     Base modèle + CRUD générique
│   ├── Database.php                  Connexion PDO
│   ├── Session.php                   Session + messages flash
│   ├── Csrf.php                      Génération / validation des jetons
│   ├── Validator.php                 Validation de formulaires
│   ├── Auth.php                      Utilitaires de rôle (URL + libellé)
│   └── helpers.php                   config(), base_path(), url(), asset()
│
├── config/
│   ├── app.php                       Nom, URL, base_path, session, chemins
│   ├── database.php                  Hôte, port, base, identifiants
│   └── routes.php                    Table des routes
│
├── database/
│   ├── migrations/
│   │   ├── 001_schema.sql            10 tables (CREATE TABLE IF NOT EXISTS)
│   │   └── 002_index.sql             7 index de lecture
│   └── seed_demo.sql                 Jeu de démonstration (rejouable)
│
├── public/
│   ├── index.php                     Front controller
│   ├── .htaccess                     Défense en profondeur
│   └── assets/
│       ├── css/                      auth.css, home.css, dashboard.css
│       ├── js/                       home.js, dashboard.js
│       └── images/                   (vide)
│
├── storage/                          Hors racine web
│   ├── .htaccess
│   ├── logs/
│   └── uploads/                      (vide — aucune photo en base)
│
├── vendor/                           Généré par Composer
├── .env                              Configuration locale
├── .env.example                      Modèle de configuration
├── .htaccess                         Réécriture d'URL + durcissement
├── composer.json
└── README.md
```

### Namespaces (PSR-4)

| Namespace | Dossier |
|---|---|
| `App\Core\` | `core/` |
| `App\Controllers\` | `app/Controllers/` |
| `App\Models\` | `app/Models/` |
| `App\Middleware\` | `app/Middleware/` |

### Flux d'une requête

```text
Navigateur
   ↓
.htaccess (racine)  ── rejette app/, core/, config/, database/, storage/,
   │                     vendor/ et tout fichier caché (.env, .git)
   ↓
public/index.php     ── définit ROOT_PATH / APP_PATH / CONFIG_PATH
   │                  ── charge vendor/autoload.php (Composer)
   │                  ── Env::load('.env')
   │                  ── Session::start()
   │                  ── retire le préfixe /petites_annonces de l'URI
   ↓
App\Core\Router      ── recherche la route (méthode + chemin + {param})
   │                  ── exécute les middlewares dans l'ordre
   ↓
Contrôleur           ── appelle un modèle (1 appel = 1 requête SQL)
   │                  ── viewWithLayout() ou view()
   ↓
Vue PHP              ── affiche uniquement (aucune requête SQL, échappement
                      systématique avec htmlspecialchars)
```

### Séparation des responsabilités

- **Contrôleur** : orchestre, ne contient **aucun SQL** et ne décide **d'aucune autorisation** (c'est le rôle des middlewares).
- **Modèle** : contient **tout** le SQL, en requêtes préparées uniquement. Chaque méthode = une requête (pas de N+1 : les compteurs sont des sous-requêtes corrélées ou des agrégats).
- **Vue** : n'affiche que les données reçues, échappées en sortie.

### Helpers globaux (`core/helpers.php`, chargés par Composer)

| Fonction | Rôle | Exemple |
|---|---|---|
| `config($key, $default)` | Lit `config/app.php` et `config/database.php` | `config('app.base_path')` |
| `base_path($path)` | Chemin public relatif | `base_path('admin')` → `/petites_annonces/admin` |
| `url($path)` | URL absolue | `url('auth/login')` |
| `asset($path)` | URL d'un fichier statique | `asset('assets/css/home.css')` |

Helpers du layout connecté (`app/Views/layouts/partials/connected-helpers.php`) : `dashIcon`, `dashIsActive`, `dashInitiales`, `dashAvatar`, `dashMenuParRole`, `dashBadgeStatut`, `dashTypeAnnonce`, `dashSuffixePrix`, `dashPrixAffiche`, `dashDateFr`, `dashTempsRelatif`, `dashPhoto`, `formatFcfa`.

---

## 4. Installation

**Prérequis** : XAMPP (Apache + MariaDB/MySQL), PHP >= 8.2, Composer.

1. **Placer le projet dans le dossier web** : `C:\xampp\htdocs\petites_annonces`. L'application est alors accessible sur <http://localhost/petites_annonces/>.

2. **Installer les dépendances** (génère `vendor/`) :

   ```bash
   composer install
   ```

3. **Créer le fichier d'environnement** :

   ```bash
   copy .env.example .env
   ```

   Sous PowerShell : `Copy-Item .env.example .env`

4. **Créer la base de données** :

   ```sql
   CREATE DATABASE petites_annonces
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_general_ci;
   ```

5. **Appliquer le schéma et les index** :

   ```bash
   mysql -u root --default-character-set=utf8mb4 petites_annonces < database/migrations/001_schema.sql
   mysql -u root --default-character-set=utf8mb4 petites_annonces < database/migrations/002_index.sql
   ```

   Les deux fichiers utilisent `IF NOT EXISTS` : ils sont sûrs à rejouer et ne contiennent **aucun `DROP TABLE`**.

6. **(Optionnel) Charger le jeu de démonstration** :

   ```bash
   mysql -u root --default-character-set=utf8mb4 petites_annonces < database/seed_demo.sql
   ```

   `seed_demo.sql` est **rejouable** (`ON DUPLICATE KEY UPDATE`) et **non destructif** : il ne contient aucun `DROP`, aucun `TRUNCATE`, et ne modifie jamais la table `users`. Il insère 9 catégories, 14 villes, 12 annonces, des messages, des signalements et des entrées d'audit. Il ne crée aucune photo : `storage/uploads/` reste vide et les vues affichent une image de remplacement.

7. **Démarrer Apache et MySQL** depuis le panneau XAMPP.

8. **Ouvrir** <http://localhost/petites_annonces/>.

> ⚠️ Le fichier `seed_demo.sql` **ne crée aucun compte** : il référence les UUID des comptes `users` déjà présents en base. Pour disposer d'un compte modérateur ou administrateur, il faut créer le compte via `/auth/register` puis mettre à jour son `role` directement en base.

---

## 5. Configuration

| Fichier | Rôle |
|---|---|
| `.env` | Variables d'environnement (**ne pas versionner**) |
| `.env.example` | Modèle de configuration |
| `config/app.php` | Nom, URL, `base_path`, session, chemins de stockage |
| `config/database.php` | Paramètres de connexion, lus depuis `.env` |
| `config/routes.php` | Table des routes et de leurs middlewares |

### Variables d'environnement

| Variable | Rôle | Valeur par défaut |
|---|---|---|
| `APP_NAME` | Nom affiché | `PetitesAnnonces.sn` |
| `APP_ENV` | Environnement | `local` |
| `APP_DEBUG` | Mode débogage | `true` |
| `APP_URL` | URL absolue de l'application | `http://localhost/petites_annonces` |
| `APP_BASE_PATH` | Préfixe public retiré de l'URI | `/petites_annonces` |
| `APP_TIMEZONE` | Fuseau horaire | `Africa/Dakar` |
| `DB_DRIVER` | Driver PDO | `mysql` |
| `DB_HOST` | Hôte MariaDB | `localhost` |
| `DB_PORT` | Port | `3306` |
| `DB_DATABASE` | Nom de la base | `petites_annonces` |
| `DB_USERNAME` | Identifiant | `root` |
| `DB_PASSWORD` | Mot de passe | *(vide)* |
| `DB_CHARSET` | Encodage | `utf8mb4` |
| `SESSION_LIFETIME` | Durée de session (secondes) | `7200` |

> **Si l'application est déplacée** (autre dossier, sous-dossier, racine du serveur), ajuster `APP_URL` et `APP_BASE_PATH` en conséquence.
>
> Le lecteur `.env` est maison (`App\Core\Env`) : pas de dépendance externe, l'application reste installable hors ligne.

---

## 6. Base de données

### Schéma (10 tables)

| Table | Clé primaire | Rôle |
|---|---|---|
| `users` | `char(36)` UUID | Comptes, rôle (`member`/`moderateur`/`admin`), statut |
| `categories` | `int(11)` AUTO_INCREMENT | Catégories (avec `parent_id`) |
| `villes` | `int(11)` AUTO_INCREMENT | Villes / départements / régions (avec `parent_id`) |
| `annonces` | `char(36)` UUID | Annonces (titre, prix, type, statut, expiration) |
| `photos` | `char(36)` UUID | Photos des annonces — **table vide** |
| `favoris` | composite `(user_id, annonce_id)` | Favoris des membres |
| `messages` | `char(36)` UUID | Messages entre membres |
| `signalements` | `char(36)` UUID | Signalements d'annonces |
| `audit_logs` | `char(36)` UUID | Journal d'audit de la plateforme |
| `alertes` | `char(36)` UUID | Alertes de recherche — hors périmètre actuel |

### Valeurs de statut réellement utilisées

```text
users.role            : 'member' | 'moderateur' | 'admin' | ''
users.status          : 'active' | 'suspendu' | 'banni' | ''
annonces.status       : 'en_attente' | 'active' | 'expirée' | 'suspendue'
annonces.type_annonce : 'vente' | 'location' | 'don' | 'recherche'
categories.status     : 'active' | 'inactive' | ''
signalements.status   : 'en_attente' | 'traite' | 'rejete' | ''
villes.type           : varchar libre (ex. 'region', 'departement')
```

> Ces valeurs sont celles **réellement utilisées en base**. Plusieurs ENUM contiennent une valeur vide (`''`) : le schéma est reproduit tel quel, sans ajout ni suppression de contrainte.
>
> Seules les annonces au statut **`active`** sont visibles sur l'accueil public. Les autres statuts ne sont visibles que dans les espaces connectés.

### Index (`002_index.sql`)

Sept index de lecture ont été ajoutés pour les tableaux de bord : `annonces(status, created_at)`, `annonces(user_id, created_at)`, `signalements(status, created_at)`, `messages(annonces_id, created_at)`, `messages(receiver_id, lu)`, `audit_logs(user_id, created_at)` et `audit_logs(created_at)`.

> Ces index sont justifiés par la **forme** des requêtes, pas par une mesure de performance : sur un jeu de démonstration de quelques dizaines de lignes, l'optimiseur préfère le balayage complet.

---

## 7. Routage, middlewares et sécurité

### Table des routes (`config/routes.php`)

```php
[
    'method'     => 'GET',
    'path'       => '/admin/journal',
    'handler'    => [AdminController::class, 'journal'],
    'middleware' => [AuthMiddleware::class, [RoleMiddleware::class, 'admin']],
],
```

Un middleware peut être une simple classe → `AuthMiddleware::class`, ou un tableau paramétré → `[RoleMiddleware::class, 'admin']`.

### Middlewares disponibles

| Middleware | Effet |
|---|---|
| `AuthMiddleware` | 302 vers `/auth/login` si l'utilisateur n'est pas connecté |
| `GuestMiddleware` | 302 vers l'accueil si l'utilisateur est déjà connecté |
| `RoleMiddleware` | **403** si le rôle en session n'est pas le rôle attendu |

### Cloisonnement des rôles (strict, sans hiérarchie)

| Espace | Rôle requis | Anonyme | Autre rôle |
|---|---|---|---|
| `/membre/*` | `member` | 302 → `/auth/login` | 403 |
| `/moderateur/*` | `moderateur` | 302 → `/auth/login` | 403 |
| `/admin/*` | `admin` | 302 → `/auth/login` | 403 |

Il n'existe **aucune hiérarchie** : un administrateur n'accède pas à l'espace membre, un modérateur non plus. Les middlewares lisent **uniquement la session serveur** : aucune donnée fournie par le client ne participe à une décision d'autorisation.

### Mesures de sécurité en place

- **En-têtes HTTP** : `.htaccess` racine → réécriture vers `public/index.php`, blocage (403) de `app/`, `core/`, `config/`, `database/`, `storage/`, `vendor/`, de `composer.json`, de `README.md` et de tout fichier caché (`.env`, `.git`).
- **Défense en profondeur** : `public/.htaccess` (idem pour un accès direct à `/public/`), plus un `.htaccess` dans `app/`, `core/`, `database/` et `storage/`.
- **SQL** : 100 % des requêtes sont préparées (`ATTR_EMULATE_PREPARES = false`, préparation native). Les `LIMIT` sont bornés par un entier puis liés en `PDO::PARAM_INT`.
- **Requêtes par ligne (N+1)** : interdites par construction — les compteurs sont des sous-requêtes corrélées ou des agrégats.
- **Mots de passe** : `password_hash()` / `password_verify()`, jamais stockés ni affichés en clair.
- **CSRF** : jeton de session sur chaque POST (`App\Core\Csrf`), comparé avec `hash_equals()`.
- **Session** : `session_regenerate_id(true)` à l'inscription et à la connexion ; cookie `httponly` + `samesite=Lax` + `secure` si HTTPS.
- **Échappement** : toute donnée issue de la base est échappée avec `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Fuite d'information** : messages d'erreur génériques, pages 403/404/500 neutres, détails techniques uniquement dans les logs serveur.
- **Erreurs SQL** : le message technique est envoyé à `error_log()` et une exception générique est renvoyée à l'utilisateur.

---

## 8. Ce qui n'est PAS encore implémenté

Cette section est volontairement explicite, afin d'éviter toute confusion entre l'interface affichée et les fonctionnalités réellement disponibles.

| Fonctionnalité | État |
|---|---|
| Page de consultation d'une annonce (`/annonces`, `/annonces/{id}`) | ❌ Non implémentée (404) |
| Page de liste des annonces avec filtres | ❌ Non implémentée (404) |
| Moteur de recherche | ❌ Non implémenté (le formulaire de l'accueil renvoie vers `/annonces`) |
| Création / modification / suppression d'une annonce | ❌ Non implémentée |
| Ajout / retrait d'un favori | ❌ Non implémenté |
## 9. Outils de contrôle

Les scripts de test ne sont **pas** dans le dépôt : ils vivent hors de l'application, dans `C:\xampp\htdocs\_backups\`.

| Script | Rôle |
|---|---|
| `run_unit_tests.php` | Tests unitaires du socle (Env, Validator, Csrf, Session, Router, Database) |
| `run_e2e_tests.php` | Tests HTTP de bout en bout |
| `run_http_tests.php` | Tests HTTP complémentaires |
| `dev_router.php` | Reproduit le `.htaccess` pour le serveur PHP intégré |
| `http_sim.php` | Simulateur de requête HTTP en CLI |

```bash
php C:\xampp\htdocs\_backups\run_unit_tests.php
php C:\xampp\htdocs\_backups\run_e2e_tests.php
```

Vérification rapide de la syntaxe de tous les fichiers PHP :

```bash
for /r %f in (*.php) do @php -l "%f"
```

---

## 10. État Git

- **Dépôt distant** : `https://github.com/mohamadousouleye27-del/petites_annonces.git`
- **Branche de travail** : `souleymane`
- **Dépendances** : `vendor/` est versionné (généré par Composer).

> ⚠️ **Point d'attention relevé lors de l'audit** : le fichier `.env` est actuellement **suivi par Git** (`git ls-files` le liste) et aucun fichier `.gitignore` n'existe à la racine du projet.
>
> Pour le corriger : `git rm --cached .env`, puis ajouter un `.gitignore` contenant au minimum `.env`.
>
> Ce point est signalé, **mais n'a pas été modifié** : cette étape est exclusivement documentaire.

---

*Documentation mise à jour à l'issue de l'étape 7.6 (finalisation et audit), à partir de l'état réel du code et de la base de données.*
