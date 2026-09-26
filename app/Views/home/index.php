<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <meta name="description" content="PetitesAnnonces.sn — Achetez, vendez et échangez en toute confiance à Dakar et partout au Sénégal.">

    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                        inter: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        stone: {
                            50: '#fafaf9',
                            100: '#f5f5f4',
                            200: '#e7e5e4',
                            300: '#d6d3d1',
                            400: '#a8a29e',
                            500: '#78716c',
                            600: '#57534e',
                            700: '#44403c',
                            800: '#292524',
                            900: '#1c1917',
                        },
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        amber: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        },
                    },
                },
            },
        };
    </script>

    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="<?= asset('assets/css/home.css') ?>">
</head>
<body class="font-inter bg-stone-50 text-stone-900 antialiased">

<?php
// Helper pour formater les prix en FCFA
function formatPrix(int $prix): string {
    return number_format($prix, 0, ',', ' ') . ' FCFA';
}

// Helper pour les icônes de catégories
function iconeCategorie(string $nom): string {
    $icones = [
        'immeuble' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/></svg>',
        'voiture' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 17h14M6 17l-1.5-5.5A2 2 0 016.5 10h11a2 2 0 012 1.5L21 17M6 17a2 2 0 104 0M14 17a2 2 0 104 0M8 10l1-3h6l1 3"/></svg>',
        'electronique' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>',
        'telephone' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
        'mode' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
        'maison' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>',
        'emploi' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
        'loisirs' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'autres' => '<svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>',
    ];

    return $icones[$nom] ?? $icones['autres'];
}
?>

<!-- ============================================
     HEADER
     ============================================ -->
<header class="bg-stone-900 border-b border-stone-800 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <!-- Logo -->
            <a href="<?= base_path('/') ?>" class="flex items-center gap-2 shrink-0">
                <div class="w-9 h-9 lg:w-10 lg:h-10 rounded-xl bg-amber-400 flex items-center justify-center">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-stone-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                </div>
                <span class="font-poppins font-bold text-white text-lg lg:text-xl tracking-tight">
                    Petites<span class="text-amber-400">Annonces</span><span class="text-teal-400">.sn</span>
                </span>
            </a>

            <!-- Navigation desktop -->
            <nav class="hidden lg:flex items-center gap-8">
                <a href="<?= base_path('/') ?>" class="text-white font-medium text-sm hover:text-amber-400 transition-colors">Accueil</a>
                <a href="<?= base_path('annonces') ?>" class="text-stone-300 font-medium text-sm hover:text-amber-400 transition-colors">Annonces</a>
                <a href="<?= base_path('annonces') ?>" class="text-stone-300 font-medium text-sm hover:text-amber-400 transition-colors">Catégories</a>
                <a href="<?= base_path('annonces') ?>" class="text-stone-300 font-medium text-sm hover:text-amber-400 transition-colors">Contact</a>
            </nav>

            <!-- Actions desktop -->
            <div class="hidden lg:flex items-center gap-3">
                <a href="<?= base_path('auth/login') ?>" class="text-stone-300 font-medium text-sm hover:text-white transition-colors px-4 py-2">
                    Se connecter
                </a>
                <a href="<?= base_path('annonces/creer') ?>" class="bg-amber-400 hover:bg-amber-300 text-stone-900 font-semibold text-sm px-5 py-2.5 rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-amber-400/30">
                    + Publier
                </a>
            </div>

            <!-- Bouton menu mobile -->
            <button id="menu-mobile-btn" class="lg:hidden text-stone-300 hover:text-white p-2" aria-label="Ouvrir le menu" aria-expanded="false">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Menu mobile -->
    <div id="mobile-menu" class="hidden lg:hidden bg-stone-900 border-t border-stone-800 px-4 pb-6 pt-2">
        <nav class="flex flex-col gap-1">
            <a href="<?= base_path('/') ?>" class="text-white font-medium text-sm py-3 px-2 hover:bg-stone-800 rounded-lg transition-colors">Accueil</a>
            <a href="<?= base_path('annonces') ?>" class="text-stone-300 font-medium text-sm py-3 px-2 hover:bg-stone-800 rounded-lg transition-colors">Annonces</a>
            <a href="<?= base_path('annonces') ?>" class="text-stone-300 font-medium text-sm py-3 px-2 hover:bg-stone-800 rounded-lg transition-colors">Catégories</a>
            <a href="<?= base_path('annonces') ?>" class="text-stone-300 font-medium text-sm py-3 px-2 hover:bg-stone-800 rounded-lg transition-colors">Contact</a>
        </nav>
        <div class="flex flex-col gap-3 mt-4 pt-4 border-t border-stone-800">
            <a href="<?= base_path('auth/login') ?>" class="text-stone-300 font-medium text-sm text-center py-2.5 border border-stone-700 rounded-xl hover:border-stone-500 transition-colors">
                Se connecter
            </a>
            <a href="<?= base_path('annonces/creer') ?>" class="bg-amber-400 hover:bg-amber-300 text-stone-900 font-semibold text-sm text-center py-2.5 rounded-xl transition-colors">
                + Publier une annonce
            </a>
        </div>
    </div>
</header>

<!-- ============================================
     HERO
     ============================================ -->
<section class="hero-gradient">
    <div class="hero-grid-overlay"></div>
    <div class="hero-glow-1"></div>
    <div class="hero-glow-2"></div>
    <div class="hero-glow-3"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="text-center max-w-3xl mx-auto">
            <!-- Badge -->
            <div class="hero-badge inline-flex items-center gap-2 rounded-full px-4 py-2 mb-8 animate-fade-in-up">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-teal-400"></span>
                </span>
                <span class="text-teal-100 text-sm font-medium">14 618 annonces actives au Sénégal</span>
            </div>

            <!-- Titre -->
            <h1 class="font-poppins font-bold text-white text-4xl sm:text-5xl lg:text-6xl leading-tight mb-6 animate-fade-in-up delay-100">
                Trouvez ce que vous cherchez
                <span class="block text-amber-400">près de chez vous</span>
            </h1>

            <!-- Description -->
            <p class="text-stone-300 text-lg lg:text-xl mb-10 max-w-2xl mx-auto animate-fade-in-up delay-200">
                Achetez, vendez et échangez en toute confiance à Dakar et partout au Sénégal.
            </p>

            <!-- Barre de recherche -->
            <form action="<?= base_path('annonces') ?>" method="GET" class="search-bar bg-white rounded-2xl p-2 sm:p-3 flex flex-col lg:flex-row items-stretch lg:items-center gap-2 animate-fade-in-up delay-300">
                <!-- Champ recherche -->
                <div class="flex-1 flex items-center gap-3 px-3 py-2.5">
                    <svg class="w-5 h-5 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input
                        type="text"
                        name="q"
                        placeholder="Que recherchez-vous ?"
                        class="w-full bg-transparent outline-none text-stone-800 placeholder-stone-400 text-base"
                        aria-label="Que recherchez-vous ?"
                    >
                </div>

                <!-- Séparateur -->
                <div class="hidden lg:block w-px h-8 bg-stone-200"></div>

                <!-- Catégorie -->
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <svg class="w-5 h-5 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <select name="categorie" class="search-select bg-transparent outline-none text-stone-700 text-base pr-8 cursor-pointer w-full lg:w-auto" aria-label="Catégorie">
                        <option value="">Toutes catégories</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?= htmlspecialchars($categorie['nom']) ?>"><?= htmlspecialchars($categorie['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Séparateur -->
                <div class="hidden lg:block w-px h-8 bg-stone-200"></div>

                <!-- Localisation -->
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <svg class="w-5 h-5 text-stone-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <select name="localisation" class="search-select bg-transparent outline-none text-stone-700 text-base pr-8 cursor-pointer w-full lg:w-auto" aria-label="Localisation">
                        <option value="Dakar" selected>Dakar</option>
                        <option value="Thiès">Thiès</option>
                        <option value="Saint-Louis">Saint-Louis</option>
                        <option value="Diourbel">Diourbel</option>
                        <option value="Kaolack">Kaolack</option>
                        <option value="Ziguinchor">Ziguinchor</option>
                        <option value="Louga">Louga</option>
                        <option value="Fatick">Fatick</option>
                        <option value="Kolda">Kolda</option>
                        <option value="Matam">Matam</option>
                        <option value="Kaffrine">Kaffrine</option>
                        <option value="Kédougou">Kédougou</option>
                        <option value="Sédhiou">Sédhiou</option>
                        <option value="Tambacounda">Tambacounda</option>
                    </select>
                </div>

                <!-- Bouton Rechercher -->
                <button type="submit" class="bg-amber-400 hover:bg-amber-300 text-stone-900 font-semibold text-base px-8 py-3.5 rounded-xl transition-all duration-300 hover:shadow-lg hover:shadow-amber-400/40 flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    Rechercher
                </button>
            </form>

            <!-- Recherches rapides -->
            <div class="mt-8 flex flex-wrap items-center justify-center gap-2 animate-fade-in-up delay-400">
                <span class="text-stone-400 text-sm mr-1">Recherches rapides :</span>
                <?php
                $recherchesRapides = ['iPhone', 'Toyota', 'Appartement Dakar', 'MacBook', 'Samsung'];
                foreach ($recherchesRapides as $recherche):
                ?>
                    <a href="<?= base_path('annonces') ?>?q=<?= urlencode($recherche) ?>" class="text-stone-300 text-sm bg-white/5 hover:bg-white/10 border border-white/10 hover:border-amber-400/50 rounded-full px-4 py-1.5 transition-all duration-300">
                        <?= htmlspecialchars($recherche) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     CATÉGORIES
     ============================================ -->
<section class="py-16 lg:py-24 bg-stone-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- En-tête de section -->
        <div class="flex items-end justify-between mb-10 lg:mb-14">
            <div>
                <h2 class="font-poppins font-bold text-3xl lg:text-4xl text-stone-900">Parcourir par catégorie</h2>
                <p class="text-stone-500 mt-2 text-lg">Trouvez rapidement ce que vous cherchez</p>
            </div>
            <a href="<?= base_path('annonces') ?>" class="hidden sm:inline-flex items-center gap-2 text-teal-700 font-semibold hover:text-teal-600 transition-colors group">
                Voir tout
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>

        <!-- Grille des catégories -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 lg:gap-6">
            <?php foreach ($categories as $categorie): ?>
                <a href="<?= base_path('annonces') ?>?categorie=<?= urlencode($categorie['nom']) ?>" class="category-card bg-white rounded-2xl p-6 lg:p-8 flex flex-col items-center text-center group">
                    <div class="category-icon w-14 h-14 lg:w-16 lg:h-16 rounded-2xl bg-stone-100 text-stone-600 flex items-center justify-center mb-4">
                        <?= iconeCategorie($categorie['icone']) ?>
                    </div>
                    <h3 class="font-poppins font-semibold text-stone-900 text-sm lg:text-base mb-1">
                        <?= htmlspecialchars($categorie['nom']) ?>
                    </h3>
                    <p class="text-stone-400 text-xs lg:text-sm">
                        <?= number_format((int)$categorie['nombre'], 0, ',', ' ') ?> annonces
                    </p>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Lien mobile "Voir tout" -->
        <div class="sm:hidden mt-8 text-center">
            <a href="<?= base_path('annonces') ?>" class="inline-flex items-center gap-2 text-teal-700 font-semibold">
                Voir tout
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- ============================================
     ANNONCES RÉCENTES
     ============================================ -->
<section class="py-16 lg:py-24 bg-stone-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- En-tête de section -->
        <div class="flex items-end justify-between mb-10 lg:mb-14">
            <div>
                <h2 class="font-poppins font-bold text-3xl lg:text-4xl text-stone-900">Annonces récentes</h2>
                <p class="text-stone-500 mt-2 text-lg">Les dernières annonces publiées</p>
            </div>
            <a href="<?= base_path('annonces') ?>" class="hidden sm:inline-flex items-center gap-2 text-teal-700 font-semibold hover:text-teal-600 transition-colors group">
                Voir toutes
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>

        <!-- Grille des annonces -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 lg:gap-6">
            <?php foreach ($annonces as $annonce): ?>
                <article class="annonce-card bg-white rounded-2xl overflow-hidden group">
                    <!-- Image -->
                    <div class="annonce-image-wrapper relative h-48 lg:h-52">
                        <img
                            src="<?= htmlspecialchars($annonce['image']) ?>"
                            alt="<?= htmlspecialchars($annonce['titre']) ?>"
                            class="annonce-image w-full h-full object-cover"
                            loading="lazy"
                        >
                        <!-- Badge -->
                        <?php if (!empty($annonce['badge'])): ?>
                            <span class="absolute top-3 left-3 bg-amber-400 text-stone-900 text-xs font-semibold px-3 py-1 rounded-full shadow-md">
                                <?= htmlspecialchars($annonce['badge']) ?>
                            </span>
                        <?php endif; ?>

                        <!-- Type Vente/Location -->
                        <span class="absolute bottom-3 left-3 bg-stone-900/80 backdrop-blur-sm text-white text-xs font-medium px-3 py-1 rounded-full">
                            <?= htmlspecialchars($annonce['type']) ?>
                        </span>

                        <!-- Bouton favori -->
                        <button class="favori-btn <?= $annonce['favori'] ? 'active' : '' ?> absolute top-3 right-3 w-9 h-9 rounded-full bg-white/90 backdrop-blur-sm shadow-md flex items-center justify-center text-stone-500 hover:text-red-500" aria-label="Ajouter aux favoris">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Contenu -->
                    <div class="p-4 lg:p-5">
                        <h3 class="font-poppins font-semibold text-stone-900 text-base leading-snug mb-2 line-clamp-2 group-hover:text-teal-700 transition-colors">
                            <?= htmlspecialchars($annonce['titre']) ?>
                        </h3>

                        <!-- Prix -->
                        <div class="mb-3">
                            <span class="font-poppins font-bold text-teal-700 text-lg">
                                <?= formatPrix((int)$annonce['prix']) ?>
                            </span>
                            <?php if (!empty($annonce['suffixe'])): ?>
                                <span class="text-stone-400 text-sm"><?= htmlspecialchars($annonce['suffixe']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Localisation -->
                        <div class="flex items-center gap-1.5 text-stone-500 text-sm mb-2">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?= htmlspecialchars($annonce['localisation']) ?>
                        </div>

                        <!-- Vues + Date -->
                        <div class="flex items-center justify-between pt-3 border-t border-stone-100">
                            <span class="flex items-center gap-1.5 text-stone-400 text-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <?= number_format((int)$annonce['vues'], 0, ',', ' ') ?> vues
                            </span>
                            <span class="text-stone-400 text-xs"><?= htmlspecialchars($annonce['date']) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Lien mobile "Voir toutes" -->
        <div class="sm:hidden mt-8 text-center">
            <a href="<?= base_path('annonces') ?>" class="inline-flex items-center gap-2 text-teal-700 font-semibold">
                Voir toutes
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- ============================================
     BANNIÈRE CTA
     ============================================ -->
<section class="py-16 lg:py-24 bg-stone-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="cta-gradient rounded-3xl lg:rounded-[2.5rem] px-6 py-14 lg:px-16 lg:py-20 text-center relative">
            <div class="cta-glow"></div>
            <div class="cta-glow-2"></div>

            <div class="relative">
                <h2 class="font-poppins font-bold text-white text-3xl lg:text-5xl mb-4">
                    Vous avez quelque chose à vendre ?
                </h2>
                <p class="text-teal-50 text-lg lg:text-xl mb-10 max-w-2xl mx-auto">
                    Publiez votre annonce gratuitement et rejoignez +14 000 vendeurs actifs.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="<?= base_path('annonces/creer') ?>" class="bg-amber-400 hover:bg-amber-300 text-stone-900 font-semibold text-base px-8 py-4 rounded-xl transition-all duration-300 hover:shadow-xl hover:shadow-amber-400/30 w-full sm:w-auto">
                        Publier gratuitement
                    </a>
                    <a href="<?= base_path('auth/register') ?>" class="bg-white/10 hover:bg-white/20 text-white font-semibold text-base px-8 py-4 rounded-xl border border-white/20 backdrop-blur-sm transition-all duration-300 w-full sm:w-auto">
                        Créer un compte
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     STATISTIQUES
     ============================================ -->
<section class="bg-white border-t border-stone-200 py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <?php foreach ($stats as $stat): ?>
                <div class="text-center">
                    <div class="stat-value text-4xl lg:text-5xl font-bold mb-2">
                        <?= htmlspecialchars($stat['valeur']) ?>
                    </div>
                    <p class="text-stone-500 font-medium"><?= htmlspecialchars($stat['label']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     FOOTER
     ============================================ -->
<footer class="bg-stone-900 text-stone-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 lg:gap-8">
            <!-- Logo + Slogan + Réseaux -->
            <div class="lg:col-span-2">
                <a href="<?= base_path('/') ?>" class="flex items-center gap-2 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-400 flex items-center justify-center">
                        <svg class="w-6 h-6 text-stone-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    <span class="font-poppins font-bold text-white text-xl tracking-tight">
                        Petites<span class="text-amber-400">Annonces</span><span class="text-teal-400">.sn</span>
                    </span>
                </a>
                <p class="text-stone-400 mb-6 max-w-sm">
                    Achetez, vendez, trouvez près de chez vous.
                </p>
                <div class="flex items-center gap-3">
                    <a href="#" class="social-icon w-10 h-10 rounded-xl bg-stone-800 flex items-center justify-center text-stone-300" aria-label="Facebook">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon w-10 h-10 rounded-xl bg-stone-800 flex items-center justify-center text-stone-300" aria-label="Twitter / X">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon w-10 h-10 rounded-xl bg-stone-800 flex items-center justify-center text-stone-300" aria-label="Instagram">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                            <path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                        </svg>
                    </a>
                    <a href="#" class="social-icon w-10 h-10 rounded-xl bg-stone-800 flex items-center justify-center text-stone-300" aria-label="LinkedIn">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Colonne À propos -->
            <div>
                <h3 class="font-poppins font-semibold text-white mb-4">À propos</h3>
                <ul class="space-y-3">
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Qui sommes-nous ?</a></li>
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Comment ça marche</a></li>
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Nos engagements</a></li>
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Blog</a></li>
                </ul>
            </div>

            <!-- Colonne Catégories -->
            <div>
                <h3 class="font-poppins font-semibold text-white mb-4">Catégories</h3>
                <ul class="space-y-3">
                    <?php foreach (array_slice($categories, 0, 5) as $categorie): ?>
                        <li>
                            <a href="<?= base_path('annonces') ?>?categorie=<?= urlencode($categorie['nom']) ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">
                                <?= htmlspecialchars($categorie['nom']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Colonne Aide & Contact -->
            <div>
                <h3 class="font-poppins font-semibold text-white mb-4">Aide & Contact</h3>
                <ul class="space-y-3">
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Centre d'aide</a></li>
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Contactez-nous</a></li>
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Conditions d'utilisation</a></li>
                    <li><a href="<?= base_path('annonces') ?>" class="footer-link text-stone-400 hover:text-amber-400 text-sm">Confidentialité</a></li>
                </ul>
                <!-- WhatsApp -->
                <a href="https://wa.me/221770000000" target="_blank" rel="noopener" class="mt-6 inline-flex items-center gap-2 bg-[#25D366]/10 border border-[#25D366]/30 text-[#25D366] text-sm font-medium px-4 py-2.5 rounded-xl hover:bg-[#25D366]/20 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Support WhatsApp
                </a>
            </div>
        </div>

        <!-- Barre inférieure -->
        <div class="mt-12 pt-8 border-t border-stone-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-stone-500 text-sm text-center sm:text-left">
                © 2026 PetitesAnnonces.sn – Tous droits réservés
            </p>
            <p class="text-stone-500 text-sm">
                Fait avec <span class="text-red-500">❤</span> au Sénégal <span>🇸🇳</span>
            </p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="<?= asset('assets/js/home.js') ?>"></script>
</body>
</html>