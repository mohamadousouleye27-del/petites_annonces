<?php
/**
 * Vue : profil du membre (GET /membre/profil).
 *
 * Vue de CONTENU uniquement, injectée dans $content du layout
 * app/Views/layouts/connected.php.
 *
 * WRAPPER : le HTML commun aux trois espaces connectés (membre, modérateur,
 * administrateur) est désormais centralisé dans le partial
 *   app/Views/layouts/partials/profil-contenu.php
 * Ce fichier ne fait donc plus que fournir les données à ce partial. Le
 * rendu est visuellement identique à l'ancien profil membre : mêmes classes
 * CSS, mêmes blocs (identité, activité, informations, sécurité).
 *
 * Données fournies par App\Controllers\MembreController::profil()
 * (lecture seule, données réelles de la base) :
 *   $profil   : informations du compte connecté, issues de la table `users`
 *               (User::trouverParId) : prenom, nom, email, telephone, ville,
 *               role, statut, avatar, email_verifie, created_at.
 *   $activite : chiffres d'activité réels (annonces, favoris, messages).
 *
 * LECTURE SEULE : aucun formulaire actif, aucune route POST, donc aucune
 * donnée ne peut être envoyée. L'utilisateur affiché est TOUJOURS
 * l'utilisateur connecté (clé de session `user_id`), jamais un identifiant
 * reçu par l'URL.
 *
 * Aucune valeur utilisateur n'est affichée sans échappement HTML (le partial
 * échappe lui-même chaque donnée via $esc).
 */

require __DIR__ . '/../layouts/partials/profil-contenu.php';
