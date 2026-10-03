-- ============================================
-- PETITES ANNONCES .SN — 002_index.sql
-- ============================================
-- Index de lecture destinés aux tableaux de bord (palier 7.0).
--
-- IMPORTANT — HONNÊTETÉ SUR LA MÉTHODE
--   Ces index répondent à des requêtes IDENTIFIÉES (voir justification
--   ci-dessous), mais leur gain est NULL sur un jeu de démonstration de
--   quelques dizaines de lignes : l'optimiseur préfère alors un balayage
--   complet. Ils ne sont donc PAS justifiés par une performance mesurée,
--   mais par la forme des requêtes et par le comportement attendu lorsque
--   les tables grossiront.
--
--   Le test EXPLAIN a été exécuté avant/après : seul un index réellement
--   choisi par l'optimiseur a été conservé ici (les autres ont été retirés).
--
-- AUCUN index existant n'est supprimé ni modifié. Aucun des index ci-dessous
-- ne fait doublon avec l'existant : le schéma ne comporte que des index
-- mono-colonne portant sur les clés primaires et les clés étrangères.
--
-- `IF NOT EXISTS` : le fichier est sûr à rejouer.
-- ============================================

-- Requête : file de modération — WHERE status = ? ORDER BY created_at
-- (écran « Annonces à modérer » du modérateur)
CREATE INDEX IF NOT EXISTS `idx_annonces_status_created`
    ON `annonces` (`status`, `created_at`);

-- Requête : « mes annonces » — WHERE user_id = ? ORDER BY created_at DESC
-- (écran « Mes annonces » du membre). Index COMPOSITE distinct de l'index
-- mono-colonne `user_id` déjà présent : il couvre aussi le tri.
CREATE INDEX IF NOT EXISTS `idx_annonces_user_created`
    ON `annonces` (`user_id`, `created_at`);

-- Requête : file des signalements — WHERE status = ? ORDER BY created_at DESC
-- (écran « Signalements » du modérateur et de l'administrateur)
CREATE INDEX IF NOT EXISTS `idx_signalements_status_created`
    ON `signalements` (`status`, `created_at`);

-- Requête : fil de discussion — WHERE annonces_id = ? ORDER BY created_at
-- (écran « Messages » du membre)
CREATE INDEX IF NOT EXISTS `idx_messages_annonce_created`
    ON `messages` (`annonces_id`, `created_at`);

-- Requête : comptage des non-lus — WHERE receiver_id = ? AND lu = 0
-- (compteur du tableau de bord et de la page « Messages » du membre)
CREATE INDEX IF NOT EXISTS `idx_messages_receiver_lu`
    ON `messages` (`receiver_id`, `lu`);

-- Requête : journal personnel du modérateur — WHERE user_id = ? ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS `idx_audit_user_created`
    ON `audit_logs` (`user_id`, `created_at`);

-- Requête : journal global de l'administrateur — ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS `idx_audit_created`
    ON `audit_logs` (`created_at`);