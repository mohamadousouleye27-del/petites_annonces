<?php

declare(strict_types=1);

/**
 * Helpers d'affichage des espaces connectés (membre, modérateur, administrateur).
 *
 * Ce fichier est chargé automatiquement par Controller::viewWithLayout()
 * lorsque le layout « layouts/connected » est utilisé (convention :
 * app/Views/<dossier du layout>/partials/<nom du layout>-helpers.php).
 * Il est donc disponible AUSSI BIEN pour la vue de contenu — rendue en
 * premier — que pour le layout lui-même.
 *
 * RÈGLES :
 *   - aucun accès à la session, à la base de données ni à la requête ;
 *   - aucune décision d'accès : le cloisonnement des rôles est assuré
 *     exclusivement côté serveur par AuthMiddleware et RoleMiddleware ;
 *   - aucune requête SQL : les données affichées proviennent du contrôleur
 *     (données statiques pendant les étapes 1 à 6) ;
 *   - toutes les fonctions sont gardées par function_exists() afin de
 *     rester sûres même en cas de double inclusion ;
 *   - les valeurs textuelles sont échappées à l'appel, dans la vue, via
 *     htmlspecialchars(..., ENT_QUOTES, 'UTF-8').
 *
 * NB : aucun libellé de rôle n'est défini ici. Les libellés français
 * (Membre / Modérateur / Administrateur) sont fournis par l'appelant et
 * proviendront de App\Core\Auth::label() (étape 5) : un seul référentiel
 * de rôles, afin d'éviter toute divergence.
 */

if (!function_exists('dashIcon')) {
    /**
     * Retourne l'icône SVG (inline) associée à un nom.
     *
     * Les icônes sont écrites en dur dans ce fichier : aucune librairie
     * externe, aucun fichier distant, aucun chargement dynamique.
     * Un nom inconnu ou vide retourne une icône neutre (aucun warning).
     *
     * @param string $nom Nom logique de l'icône (ex: 'dashboard', 'favoris')
     * @return string Balise SVG prête à être affichée
     */
    function dashIcon(string $nom): string
    {
        // Tracés disponibles (attributs communs portés par chaque <path>)
        $icones = [
            // Repli neutre : jamais d'icône manquante à l'écran
            'neutre' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14v14H5z"/>',

            // Navigation principale des trois espaces
            'dashboard'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 5h6v6H4zM14 5h6v6h-6zM4 13h6v6H4zM14 13h6v6h-6z"/>',
            'annonces'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h4m3 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
            'favoris'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
            'messages'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
            'profil'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
            'signalements'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.008M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>',
            'utilisateurs'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h4v-2a4 4 0 00-3-3.87M9 20H3v-2a4 4 0 013-3.87m9-4.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a3 3 0 10-2.5-4.6"/>',
            'categories'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h3.586a1 1 0 01.707.293L11 7h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
            // Compléments des trois espaces
            'villes'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'journal'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6m-6 4h4"/>',
            'parametres'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'deconnexion'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
            'cloche'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',

            // Éléments d'interface
            'chevron'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>',
            'plus'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
            'fleche'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3"/>',
            'recherche'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>',
            'oeil'          => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
            'valider'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>',
            'refuser'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>',
            'crayon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828z"/>',
            'poubelle'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        ];

        // Repli neutre si le nom demandé n'existe pas
        $chemins = $icones[$nom] ?? $icones['neutre'];

        return '<svg class="dash-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false">' . $chemins . '</svg>';
    }
}

if (!function_exists('dashIsActive')) {
    /**
     * Indique si un lien de navigation correspond à la page courante.
     *
     * Utilisé par le layout pour appliquer la classe « is-active » et
     * l'attribut aria-current. Fonction purement présentationnelle : aucune
     * décision d'accès n'en dépend.
     *
     * Le préfixe public de l'application (ex: « /petites_annonces ») est
     * retiré de part et d'autre avant comparaison.
     *
     * @param string $href Lien à tester (ex: base_path('membre'))
     * @param bool $exact Comparaison stricte (utile pour le tableau de bord,
     *                    qui ne doit pas être actif sur ses sous-pages)
     * @return bool True si le lien correspond à la page courante
     */
    function dashIsActive(string $href, bool $exact = false): bool
    {
        // Chemin courant, sans la query string
        $cheminCourant = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $cheminCourant = is_string($cheminCourant) ? $cheminCourant : '/';

        // Préfixe public de l'application (ex: /petites_annonces)
        $base = (string) config('app.base_path', '');

        // Retire le préfixe public puis normalise sous la forme « /segment »
        $normaliser = static function (string $chemin) use ($base): string {
            if ($base !== '' && str_starts_with($chemin, $base)) {
                $chemin = substr($chemin, strlen($base));
            }

            return '/' . trim($chemin, '/');
        };

        $courant = $normaliser($cheminCourant);
        $cible   = $normaliser($href);

        if ($exact) {
            return $courant === $cible;
        }

        // Correspondance exacte ou sur une sous-page (/membre/annonces/12)
        return $courant === $cible || str_starts_with($courant, $cible . '/');
    }
}

if (!function_exists('dashInitiales')) {
    /**
     * Construit les initiales affichées dans l'avatar du menu utilisateur.
     *
     * @param string $prenom Prénom de l'utilisateur
     * @param string $nom Nom de l'utilisateur
     * @return string Une à deux lettres majuscules, ou « ? » si vide
     */
    function dashInitiales(string $prenom, string $nom): string
    {
        $premiereLettre = static function (string $valeur): string {
            $valeur = trim($valeur);

            if ($valeur === '') {
                return '';
            }

            return function_exists('mb_substr')
                ? mb_substr($valeur, 0, 1, 'UTF-8')
                : substr($valeur, 0, 1);
        };

        $initiales = $premiereLettre($prenom) . $premiereLettre($nom);

        if ($initiales === '') {
            return '?';
        }

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($initiales, 'UTF-8')
            : strtoupper($initiales);
    }
}

if (!function_exists('formatFcfa')) {
    /**
     * Formate un montant en francs CFA.
     *
     * Nom volontairement distinct de formatPrix() (définie dans la vue
     * home/index.php) afin d'éviter toute redéclaration de fonction.
     * Tolère les séparateurs usuels : « 1 250 000 » ou « 1250000,50 ».
     *
     * @param int|float|string|null $montant Montant à formater
     * @return string Montant formaté (ex: « 1 250 000 FCFA ») ou « — » si non numérique
     */
    function formatFcfa(int|float|string|null $montant): string
    {
        if (is_string($montant)) {
            // Espaces (dont insécables) et virgule décimale
            $montant = str_replace(
                [' ', "\u{00A0}", "\u{202F}", ','],
                ['', '', '', '.'],
                $montant
            );
        }

        if (!is_numeric($montant)) {
            return '—';
        }

        return number_format((float) $montant, 0, ',', ' ') . ' FCFA';
    }
}

if (!function_exists('dashAvatar')) {
    /**
     * Affiche l'avatar de l'utilisateur connecté, ou ses initiales.
     *
     * Le projet ne dispose d'AUCUN système d'envoi d'avatars fonctionnel
     * (colonne `users.avatar` non renseignée, storage/uploads hors racine
     * web) : aucune URL d'image n'est donc inventée ici.
     *
     * Un avatar n'est rendu que si la valeur est une URL absolue http(s)
     * ou un chemin absolu serveur (« /… »). Toute autre valeur — chaîne
     * vide, chemin relatif, nom de fichier brut — est ignorée au profit des
     * initiales : une image cassée ne peut jamais être affichée.
     *
     * @param mixed $avatar Valeur issue de `users.avatar` (ou null)
     * @param string $prenom Prénom (pour les initiales de repli)
     * @param string $nom Nom (pour les initiales de repli)
     * @return string HTML de l'avatar (span d'initiales ou image)
     */
    function dashAvatar(mixed $avatar, string $prenom = '', string $nom = ''): string
    {
        $avatar = is_string($avatar) ? trim($avatar) : '';

        // Seules les URL absolues http(s) et les chemins absolus sont acceptés
        $exploitable = $avatar !== ''
            && (
                preg_match('#^https?://#i', $avatar) === 1
                || str_starts_with($avatar, '/')
            );

        if ($exploitable) {
            return '<span class="dash-avatar">'
                . '<img class="dash-avatar-img" src="'
                . htmlspecialchars($avatar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '" alt="" width="32" height="32" loading="lazy">'
                . '</span>';
        }

        // Repli propre : les initiales du prénom / nom
        return '<span class="dash-avatar" aria-hidden="true">'
            . htmlspecialchars(dashInitiales($prenom, $nom), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</span>';
    }
}

if (!function_exists('dashMenuParRole')) {
    /**
     * Navigation de repli, déduite du rôle technique de l'utilisateur.
     *
     * N'EST UTILISÉE QUE si le contrôleur n'a fourni aucune entrée de menu
     * (`$menu` vide). Les trois contrôleurs transmettent déjà leur navigation
     * : cette fonction n'est donc qu'un filet de sécurité, jamais une
     * deuxième source de vérité en fonctionnement normal.
     *
     * AUCUNE décision d'accès : les URL construites sont exactement celles
     * déjà déclarées dans config/routes.php. Le RBAC reste assuré
     * exclusivement par AuthMiddleware et RoleMiddleware.
     *
     * @param string $role Rôle technique ('member', 'moderateur', 'admin')
     * @return array<int, array<string, string|bool>> Entrées de navigation
     */
    function dashMenuParRole(string $role): array
    {
        $espaces = [
            'member'     => [
                ['label' => 'Tableau de bord', 'href' => base_path('membre'),                'icone' => 'dashboard',    'exact' => true],
                ['label' => 'Mes annonces',    'href' => base_path('membre/annonces'),       'icone' => 'annonces'],
                ['label' => 'Favoris',         'href' => base_path('membre/favoris'),        'icone' => 'favoris'],
                ['label' => 'Messages',        'href' => base_path('membre/messages'),       'icone' => 'messages'],
                ['label' => 'Mon profil',      'href' => base_path('membre/profil'),         'icone' => 'profil'],
            ],
            'moderateur' => [
                ['label' => 'Tableau de bord',  'href' => base_path('moderateur'),               'icone' => 'dashboard',    'exact' => true],
                ['label' => 'Signalements',     'href' => base_path('moderateur/signalements'),  'icone' => 'signalements'],
                ['label' => 'Annonces à modérer', 'href' => base_path('moderateur/annonces'),   'icone' => 'annonces'],
                ['label' => 'Utilisateurs',     'href' => base_path('moderateur/utilisateurs'), 'icone' => 'utilisateurs'],
                ['label' => 'Mon journal',      'href' => base_path('moderateur/journal'),      'icone' => 'journal'],
                ['label' => 'Mon profil',       'href' => base_path('moderateur/profil'),       'icone' => 'profil'],
            ],
            'admin'      => [
                ['label' => 'Tableau de bord', 'href' => base_path('admin'),               'icone' => 'dashboard',    'exact' => true],
                ['label' => 'Utilisateurs',     'href' => base_path('admin/utilisateurs'), 'icone' => 'utilisateurs'],
                ['label' => 'Annonces',        'href' => base_path('admin/annonces'),     'icone' => 'annonces'],
                ['label' => 'Catégories',      'href' => base_path('admin/categories'),   'icone' => 'categories'],
                ['label' => 'Villes',          'href' => base_path('admin/villes'),       'icone' => 'villes'],
                ['label' => 'Signalements',    'href' => base_path('admin/signalements'), 'icone' => 'signalements'],
                ['label' => "Journal d'audit", 'href' => base_path('admin/journal'),      'icone' => 'journal'],
                ['label' => 'Mon profil',      'href' => base_path('admin/profil'),       'icone' => 'profil'],
            ],
        ];

        return $espaces[$role] ?? [];
    }
}

if (!function_exists('dashBadgeStatut')) {
    /**
     * Retourne le badge d'affichage correspondant à un statut.
     *
     * PUREMENT VISUEL : ces statuts (annonces, signalements, comptes) ne
     * servent ici qu'à colorer l'interface. Aucune autorisation, aucun
     * cloisonnement de rôle et aucune règle métier ne s'appuient sur cette
     * fonction.
     *
     * Un statut inconnu est affiché tel quel (échappé) avec un style neutre.
     *
     * @param string $statut Statut technique (ex: 'en_attente', 'active')
     * @return string Balise <span> prête à être affichée
     */
    function dashBadgeStatut(string $statut): string
    {
        $statuts = [
            // Annonces et signalements en cours
            'en_attente' => ['label' => 'En attente', 'modificateur' => 'attente'],
            'traite'     => ['label' => 'Traité',     'modificateur' => 'actif'],
            'rejete'     => ['label' => 'Rejeté',     'modificateur' => 'rejete'],

            // Annonces et comptes
            'active'     => ['label' => 'Actif',      'modificateur' => 'actif'],
            'expirée'    => ['label' => 'Expirée',    'modificateur' => 'expire'],
            'expiree'    => ['label' => 'Expirée',    'modificateur' => 'expire'],
            'suspendue'  => ['label' => 'Suspendue',  'modificateur' => 'suspendu'],
            'suspendu'   => ['label' => 'Suspendu',   'modificateur' => 'suspendu'],
            'banni'      => ['label' => 'Banni',      'modificateur' => 'banni'],
        ];

        $definition = $statuts[$statut] ?? null;

        // Statut inconnu : libellé brut échappé, style neutre
        if ($definition === null) {
            $label = trim($statut) !== '' ? trim($statut) : '—';

            return '<span class="dash-badge dash-badge--neutre">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                . '</span>';
        }

        return '<span class="dash-badge dash-badge--' . $definition['modificateur'] . '">'
            . htmlspecialchars($definition['label'], ENT_QUOTES, 'UTF-8')
            . '</span>';
    }
}
if (!function_exists('dashTypeAnnonce')) {
    /**
     * Libellé français d'un type d'annonce.
     *
     * Les clés sont les valeurs exactes de l'ENUM `annonces.type_annonce`.
     * Un type inconnu est affiché tel quel (échappé), sans erreur.
     *
     * @param string $type Type technique ('vente', 'location', 'don', 'recherche')
     * @return string Libellé français
     */
    function dashTypeAnnonce(string $type): string
    {
        $libelles = [
            'vente'     => 'Vente',
            'location'  => 'Location',
            'don'       => 'Don',
            'recherche' => 'Recherche',
        ];

        return $libelles[$type] ?? $type;
    }
}

if (!function_exists('dashSuffixePrix')) {
    /**
     * Suffixe de prix associé à un type d'annonce.
     *
     * Cette information n'existe PAS en base (la colonne `prix` est un
     * montant brut) : le suffixe est déduit du type, pour l'affichage.
     *
     * @param string $type Type technique de l'annonce
     * @return string Suffixe ('/mois' pour une location, vide sinon)
     */
    function dashSuffixePrix(string $type): string
    {
        return $type === 'location' ? '/mois' : '';
    }
}

if (!function_exists('dashPrixAffiche')) {
    /**
     * Prix prêt à l'affichage, avec son suffixe éventuel.
     *
     * Un prix NULL (annonce de type « don » ou « recherche ») affiche un
     * libellé explicite plutôt qu'un montant vide.
     *
     * @param mixed $prix Prix issu de la base (chaîne décimale, int, float ou null)
     * @param string $type Type technique de l'annonce (pour le suffixe)
     * @return string Prix formaté
     */
    function dashPrixAffiche(mixed $prix, string $type = ''): string
    {
        if ($prix === null || $prix === '') {
            return $type === 'don' ? 'Gratuit' : 'À débattre';
        }

        return formatFcfa($prix) . dashSuffixePrix($type);
    }
}

if (!function_exists('dashDateFr')) {
    /**
     * Formate une date MySQL (Y-m-d H:i:s) en français lisible.
     *
     * La base renvoie un horodatage « naïf » (aucun fuseau n'est appliqué
     * par l'application : voir la limite documentée dans le rapport du
     * palier 7.0). La chaîne est donc interprétée telle quelle par
     * strtotime(), cohérent avec l'horodatage local du serveur.
     *
     * @param mixed $date Date issue de la base, ou null
     * @return string Date lisible (ex: « 28 sept. 2026 ») ou « — »
     */
    function dashDateFr(mixed $date): string
    {
        if (!is_string($date) || trim($date) === '') {
            return '—';
        }

        $horodatage = strtotime($date);

        if ($horodatage === false) {
            return '—';
        }

        $mois = [
            1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.',
            5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août',
            9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
        ];

        return date('j', $horodatage) . ' ' . $mois[(int) date('n', $horodatage)] . ' ' . date('Y', $horodatage);
    }
}

if (!function_exists('dashTempsRelatif')) {
    /**
     * Exprime une date MySQL sous forme relative (« il y a 2 heures »).
     *
     * Au-delà de 7 jours, une date absolue est affichée. Une date future
     * (horloges légèrement décalées) est ramenée à « à l'instant » : aucune
     * valeur négative n'est jamais affichée.
     *
     * @param mixed $date Date issue de la base, ou null
     * @return string Expression relative ou date absolue, « — » si invalide
     */
    function dashTempsRelatif(mixed $date): string
    {
        if (!is_string($date) || trim($date) === '') {
            return '—';
        }

        $horodatage = strtotime($date);

        if ($horodatage === false) {
            return '—';
        }

        $ecoule = time() - $horodatage;

        if ($ecoule < 0) {
            return "à l'instant";
        }

        if ($ecoule < 60) {
            return "à l'instant";
        }

        if ($ecoule < 3600) {
            $minutes = (int) floor($ecoule / 60);

            return 'il y a ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        if ($ecoule < 86400) {
            $heures = (int) floor($ecoule / 3600);

            return 'il y a ' . $heures . ' heure' . ($heures > 1 ? 's' : '');
        }

        if ($ecoule < 604800) {
            $jours = (int) floor($ecoule / 86400);

            return 'il y a ' . $jours . ' jour' . ($jours > 1 ? 's' : '');
        }

        return 'le ' . dashDateFr($date);
    }
}

if (!function_exists('dashPhoto')) {
    /**
     * Affiche la photo d'une annonce, ou un remplacement neutre.
     *
     * ÉTAT ACTUEL : la table `photos` est VIDE et `storage/uploads/` est
     * protégé par le serveur. Aucune URL externe n'est inventée. Ce helper
     * rend donc systématiquement le bloc de remplacement, et deviendra
     * effectif dès qu'un stockage d'images sera disponible (palier
     * ultérieur). Sa signature est déjà prévue pour ce cas.
     *
     * @param mixed $chemin Chemin ou URL éventuel issu de la base (ou null)
     * @param string $alt Texte alternatif (titre de l'annonce)
     * @return string HTML du visuel
     */
    function dashPhoto(mixed $chemin, string $alt = ''): string
    {
        $chemin = is_string($chemin) ? trim($chemin) : '';

        // Aucun visuel disponible : remplacement neutre, sans image cassée
        if ($chemin === '') {
            return '<div class="dash-photo-vide" role="img" aria-label="Aucune photo disponible">'
                . dashIcon('signalements')
                . '<span class="dash-photo-vide-texte">Aucune photo</span>'
                . '</div>';
        }

        return '<img class="dash-photo" src="' . htmlspecialchars($chemin, ENT_QUOTES, 'UTF-8')
            . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
    }
}

