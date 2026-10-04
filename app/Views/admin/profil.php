<?php
/**
 * Vue : profil de l'administrateur connecté (GET /admin/profil).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php (le même layout que les autres écrans de
 * l'espace Administrateur).
 *
 * WRAPPER : aucun HTML n'est écrit ici. Tout le contenu — identité, activité,
 * informations personnelles, sécurité — provient du partial commun
 *   app/Views/layouts/partials/profil-contenu.php
 * afin que les trois espaces connectés partagent exactement le même design.
 *
 * Données fournies par App\Controllers\AdminController::profil()
 * (lecture seule, données réelles de la base) :
 *   $profil   : informations de l'administrateur CONNECTÉ, issues de la
 *               table `users` via User::trouverParId sur l'identifiant de
 *               session `user_id` : prenom, nom, email, telephone, ville,
 *               role, statut, avatar, email_verifie, created_at ;
 *   $activite : statistiques d'activité réelles et propres au rôle (actions
 *               journalisées, annonces, messages).
 *
 * LECTURE SEULE : aucun <form>, aucun champ actif, aucune route POST.
 * L'utilisateur affiché est TOUJOURS l'utilisateur connecté : aucun
 * identifiant n'est lu dans l'URL, $_GET, $_POST ou $_REQUEST.
 *
 * Aucune valeur utilisateur n'est affichée sans échappement HTML (le partial
 * échappe lui-même chaque donnée via $esc).
 */

require __DIR__ . '/../layouts/partials/profil-contenu.php';