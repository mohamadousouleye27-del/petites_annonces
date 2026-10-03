-- ============================================
-- PETITES ANNONCES .SN — 001_schema.sql
-- ============================================
-- Schéma de la base `petites_annonces`, EXPORTÉ du serveur réel
-- (MariaDB 10.4.32) via SHOW CREATE TABLE, puis retranscrit à l'identique.
--
-- Ce fichier ne reconstruit PAS le schéma d'après un cahier des charges :
-- il reproduit exactement la structure en service, y compris ses
-- singularités, qui sont conservées telles quelles :
--   - `role`            : ENUM contenant une valeur vide ''
--   - `status` (users)  : ENUM contenant une valeur vide ''
--   - `status` (annonces) : libellé 'expirée' accentué
--   - `status` (categories) : ENUM contenant DEUX fois la valeur vide ('','')
--   - `type` (villes)   : varchar libre, sans ENUM ni contrainte
--   - `parent_id` (categories, villes) : indexé, SANS clé étrangère
--   - `photos.type_mime` : valeur par défaut '0'
--   - `annonces`        : aucune colonne `updated_at`
--   - largeurs `int(11)` : affichage MariaDB, conservé
--
-- AUCUN DROP TABLE : ce fichier ne détruit rien.
-- `IF NOT EXISTS` : sûr à rejouer sur une base déjà en place.
--
-- Ordre d'exécution imposé par les clés étrangères :
--   users, categories, villes, annonces, photos, favoris,
--   messages, signalements, audit_logs, alertes
-- ============================================

-- ---------- users ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id` char(36) NOT NULL,
  `prenom` varchar(80) NOT NULL,
  `nom` varchar(80) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('member','moderateur','admin','') NOT NULL DEFAULT 'member',
  `telephone` varchar(20) DEFAULT NULL,
  `ville_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','suspendu','banni','') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- categories ----------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','','') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- villes ----------
CREATE TABLE IF NOT EXISTS `villes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- annonces ----------
CREATE TABLE IF NOT EXISTS `annonces` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `titre` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `prix` decimal(12,2) DEFAULT NULL,
  `type_annonce` enum('vente','location','don','recherche') NOT NULL,
  `ville_id` int(11) NOT NULL,
  `quartier` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `nb_vues` int(11) NOT NULL DEFAULT 0,
  `status` enum('en_attente','active','expirée','suspendue') NOT NULL DEFAULT 'en_attente',
  `expire_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `categorie_id` (`categorie_id`),
  KEY `ville_id` (`ville_id`),
  CONSTRAINT `annonces_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `annonces_ibfk_2` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `annonces_ibfk_3` FOREIGN KEY (`ville_id`) REFERENCES `villes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- photos ----------
CREATE TABLE IF NOT EXISTS `photos` (
  `id` char(36) NOT NULL,
  `annonce_id` char(36) NOT NULL,
  `chemin` varchar(255) NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `taille` int(11) NOT NULL,
  `type_mime` varchar(100) NOT NULL DEFAULT '0',
  `ordre` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `annonce_id` (`annonce_id`),
  CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- favoris ----------
-- Clé primaire COMPOSITE (user_id, annonce_id), aucune colonne `id`.
CREATE TABLE IF NOT EXISTS `favoris` (
  `user_id` char(36) NOT NULL,
  `annonce_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`annonce_id`),
  KEY `annonce_id` (`annonce_id`),
  CONSTRAINT `favoris_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `favoris_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- messages ----------
-- Aucune table « conversation » : les conversations sont DÉRIVÉES
-- du regroupement (annonces_id + interlocuteur).
CREATE TABLE IF NOT EXISTS `messages` (
  `id` char(36) NOT NULL,
  `sender_id` char(36) NOT NULL,
  `receiver_id` char(36) NOT NULL,
  `annonces_id` char(36) NOT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `annonces_id` (`annonces_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`annonces_id`) REFERENCES `annonces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- signalements ----------
CREATE TABLE IF NOT EXISTS `signalements` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `annonce_id` char(36) NOT NULL,
  `raison` varchar(255) NOT NULL,
  `status` enum('en_attente','traite','rejete','') NOT NULL DEFAULT 'en_attente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `annonce_id` (`annonce_id`),
  KEY `resolved_by` (`resolved_by`),
  CONSTRAINT `signalements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `signalements_ibfk_2` FOREIGN KEY (`annonce_id`) REFERENCES `annonces` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `signalements_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- audit_logs ----------
-- `user_id` NULLABLE (ON DELETE SET NULL) : toute lecture DOIT utiliser
-- un LEFT JOIN sur users.
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `target_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- alertes ----------
-- Table hors périmètre du palier 7.0 (vestibule de recherche) :
-- reproduite pour que le schéma versionné soit COMPLET.
CREATE TABLE IF NOT EXISTS `alertes` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `mots_cles` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `categorie_id` (`categorie_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `alertes_ibfk_2` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================
-- FIN — 10 tables, structure identique au serveur réel.
-- Aucun index supplémentaire ici : voir 002_index.sql.
-- ============================================