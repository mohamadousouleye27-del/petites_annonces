# Petites Annonces

Plateforme web de petites annonces destinée au Sénégal.

## Stack technique

- PHP 8.2+ (testé avec PHP 8.5)
- POO (Programmation Orientée Objet)
- Architecture MVC
- MySQL
- TailwindCSS (CDN)
- HTML/CSS/JavaScript
- Composer (autoload PSR-4)

## Arborescence

```
petites_annonces/
├── app/
│   ├── Controllers/        Contrôleurs (AuthController, HomeController)
│   ├── Middleware/         Middlewares (Auth, Guest, Role)
│   ├── Models/             Modèles (User)
│   └── Views/              Vues (auth/, home/, layouts/, ...)
├── core/                   Socle : Router, Controller, Model, Database,
│                           Session, Validator, Csrf, Env, helpers.php
├── config/
│   ├── app.php             Configuration générale
│   ├── database.php        Configuration de la base de données
│   └── routes.php          Table des routes
├── database/
│   └── migrations/         Migrations SQL
├── public/                 Seul dossier exposé au web
│   ├── index.php           Front controller
│   ├── .htaccess
│   └── assets/             css/, js/, images/
├── storage/                Hors racine web
│   ├── logs/
│   └── uploads/
├── vendor/                 Dépendances Composer (généré)
├── .env                    Configuration locale (NE PAS versionner)
├── .env.example            Modèle de configuration
├── .htaccess               Réécriture d'URL + durcissement
├── composer.json
└── README.md
```

## Installation

1. Placer le projet dans le dossier web
   (XAMPP : `C:\xampp\htdocs\petites_annonces`).
2. Installer les dépendances :
   ```
   composer install
   ```
3. Créer le fichier d'environnement :
   ```
   copy .env.example .env
   ```
   puis adapter `DB_*` et `APP_*`.
4. Créer la base de données `petites_annonces` dans MySQL.
   (Les migrations seront ajoutées dans `database/migrations/`.)
5. Démarrer **Apache** et **MySQL** depuis le panneau XAMPP.
6. Ouvrir <http://localhost/petites_annonces/>.

## Configuration

| Fichier | Rôle |
|---|---|
| `.env` | Variables d'environnement (identifiants, URL, débogage) |
| `config/app.php` | Nom, URL, `base_path`, session, chemins de stockage |
| `config/database.php` | Paramètres de connexion, lus depuis `.env` |
| `config/routes.php` | Table des routes et de leurs middlewares |

## Namespaces (PSR-4)

| Namespace | Dossier |
|---|---|
| `App\Core\` | `core/` |
| `App\Controllers\` | `app/Controllers/` |
| `App\Models\` | `app/Models/` |
| `App\Middleware\` | `app/Middleware/` |

## Routes et middlewares

Les routes sont déclarées dans `config/routes.php` :

```php
[
    'method'     => 'POST',
    'path'       => '/auth/logout',
    'handler'    => [AuthController::class, 'logout'],
    'middleware' => [AuthMiddleware::class],
],
```

Un middleware peut être une simple classe, ou un tableau
`[Classe::class, argument]` pour un middleware paramétré
(ex. `[RoleMiddleware::class, 'admin']`).

Middlewares disponibles :

| Middleware | Effet |
|---|---|
| `AuthMiddleware` | Redirige vers la connexion si non authentifié |
| `GuestMiddleware` | Redirige vers l'accueil si déjà connecté |
| `RoleMiddleware` | HTTP 403 si le rôle en session n'est pas autorisé |

## Sécurité

- `.env`, `app/`, `core/`, `config/`, `database/`, `storage/` et `vendor/`
  sont bloqués par le `.htaccess` racine (HTTP 403) et protégés une
  seconde fois par un `.htaccess` local (défense en profondeur).
- Session : cookie `httponly`, `samesite=Lax`, `secure` en HTTPS.
- Formulaires protégés par jeton CSRF (`App\Core\Csrf`).
- Toutes les requêtes SQL utilisent des requêtes préparées.

## Tests (outils de développement)

Les scripts de contrôle se trouvent hors de l'application, dans
`C:\xampp\htdocs\_backups\` :

| Script | Rôle |
|---|---|
| `run_unit_tests.php` | Tests unitaires du socle (Env, Validator, Csrf, Session, Router, Database) |
| `run_e2e_tests.php` | Tests HTTP end-to-end via `php -S` + `dev_router.php` |
| `dev_router.php` | Reproduit le `.htaccess` pour le serveur de développement |
| `http_sim.php` | Simulateur de requête en CLI |

Exemple :

```
php C:\xampp\htdocs\_backups\run_unit_tests.php
```
