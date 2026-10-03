-- ============================================
-- PETITES ANNONCES .SN — seed_demo.sql
-- ============================================
-- Jeu de DÉMONSTRATION pour les tableaux de bord (palier 7.0).
--
-- PROPRIÉTÉS
--   1. REJOUABLE : chaque insertion utilise ON DUPLICATE KEY UPDATE.
--      Ré-exécuter le fichier RAFRAÎCHIT les lignes au lieu de les
--      dupliquer (identifiants fixes : auto-incréments 1..N et UUID
--      constants).
--   2. NON DESTRUCTIF : ce fichier ne contient AUCUN `DROP`, AUCUN
--      `TRUNCATE`, et AUCUN `UPDATE`/`DELETE` sur la table `users`.
--      Les 4 comptes existants ne sont JAMAIS modifiés : ils sont
--      seulement RÉFÉRENCÉS par leurs UUID réels.
--   3. CONFORME au schéma réel : les valeurs respectent strictement les
--      ENUM de la base et l'ordre des clés étrangères.
--   4. AUCUNE ligne dans `photos` : le stockage `storage/uploads/` reste
--      protégé et vide, et aucune URL externe n'est inventée. Les vues
--      utilisent un affichage de remplacement.
--
-- EXÉCUTION (UTF-8 obligatoire)
--   mysql -u root --default-character-set=utf8mb4 petites_annonces < seed_demo.sql
--
-- REMISE À ZÉRO : voir le bloc commenté en fin de fichier.
-- ============================================

-- ============================================
-- 1. CATÉGORIES (9 lignes, identifiants fixes)
-- ============================================
INSERT INTO `categories` (`id`, `nom`, `parent_id`, `status`) VALUES
  (1, 'Immobilier',        NULL, 'active'),
  (2, 'Véhicules',         NULL, 'active'),
  (3, 'Électronique',      NULL, 'active'),
  (4, 'Téléphones',           3, 'active'),
  (5, 'Mode',              NULL, 'active'),
  (6, 'Maison',            NULL, 'active'),
  (7, 'Emploi & Services', NULL, 'active'),
  (8, 'Loisirs',           NULL, 'active'),
  (9, 'Autres',            NULL, 'inactive')
ON DUPLICATE KEY UPDATE
  `nom` = VALUES(`nom`),
  `parent_id` = VALUES(`parent_id`),
  `status` = VALUES(`status`);

-- ============================================
-- 2. VILLES (14 lignes, identifiants fixes)
-- ============================================
-- `type` est un varchar libre en base : les valeurs ci-dessous décrivent le
-- découpage administratif sénégalais, sans contrainte ENUM.
INSERT INTO `villes` (`id`, `nom`, `type`, `parent_id`) VALUES
  (1,  'Dakar',        'region',      NULL),
  (2,  'Pikine',       'departement',    1),
  (3,  'Guédiawaye',   'departement',    1),
  (4,  'Rufisque',     'departement',    1),
  (5,  'Thiès',        'region',      NULL),
  (6,  'Saint-Louis',  'region',      NULL),
  (7,  'Diourbel',     'region',      NULL),
  (8,  'Ziguinchor',   'region',      NULL),
  (9,  'Kaolack',      'region',      NULL),
  (10, 'Louga',        'region',      NULL),
  (11, 'Fatick',       'region',      NULL),
  (12, 'Kolda',        'region',      NULL),
  (13, 'Matam',        'region',      NULL),
  (14, 'Tambacounda',  'region',      NULL)
ON DUPLICATE KEY UPDATE
  `nom` = VALUES(`nom`),
  `type` = VALUES(`type`),
  `parent_id` = VALUES(`parent_id`);

-- ============================================
-- 3. ANNONCES (12 lignes, UUID constants)
-- ============================================
-- Répartition : 6 par membre (les 2 comptes `member` existants)
-- Statuts  : 6 active, 3 en_attente, 2 expirée, 1 suspendue
-- Types    : vente, location, don, recherche (les 4 valeurs de l'ENUM)
-- Aucun compte `users` n'est créé ni modifié ici.

SET @yaya      := '34199642-b6c9-11f1-a055-f430b9a2c03b';
SET @mohamadou := '3af8c049-634a-4ecf-b931-8ecfab53d1e2';

INSERT INTO `annonces`
  (`id`, `user_id`, `categorie_id`, `titre`, `description`, `prix`,
   `type_annonce`, `ville_id`, `quartier`, `telephone`, `nb_vues`,
   `status`, `expire_at`, `created_at`)
VALUES
  ('a0000000-0000-4000-8000-000000000001', @yaya, 1,
   'Appartement 3 pièces à Almadies',
   'Appartement lumineux de 3 pièces, 2 chambres, salon, cuisine équipée. Résidence sécurisée avec gardien.',
   250000.00, 'location', 1, 'Almadies', '+221 77 123 45 67', 1240,
   'active', DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW() - INTERVAL 2 HOUR),

  ('a0000000-0000-4000-8000-000000000002', @mohamadou, 2,
   'Toyota RAV4 2019 — Très bon état',
   'Véhicule acheté neuf, entretien suivi chez le concessionnaire. Climatisation, boîte automatique.',
   18500000.00, 'vente', 1, 'Plateau', '+221 76 234 56 78', 980,
   'active', DATE_ADD(CURDATE(), INTERVAL 45 DAY), NOW() - INTERVAL 5 HOUR),

  ('a0000000-0000-4000-8000-000000000003', @mohamadou, 4,
   'Samsung Galaxy S23 Ultra 256 Go',
   'Téléphone en parfait état, facture et accessoires d''origine fournis. Aucune rayure.',
   850000.00, 'vente', 1, 'Ouakam', '+221 76 234 56 78', 143,
   'en_attente', NULL, NOW() - INTERVAL 6 HOUR),

  ('a0000000-0000-4000-8000-000000000004', @yaya, 3,
   'MacBook Pro 14 pouces M1 Pro',
   'Ordinateur portable professionnel, 16 Go de mémoire, SSD 512 Go. Batterie en excellent état.',
   1250000.00, 'vente', 1, 'Mermoz', '+221 77 123 45 67', 1870,
   'active', DATE_ADD(CURDATE(), INTERVAL 20 DAY), NOW() - INTERVAL 1 DAY),

  ('a0000000-0000-4000-8000-000000000005', @yaya, 1,
   'Chambre meublée à louer — Liberté 6',
   'Chambre meublée avec salle de bain privative, dans une maison calme. Charges comprises.',
   75000.00, 'location', 1, 'Liberté 6', '+221 77 123 45 67', 890,
   'expirée', DATE_SUB(CURDATE(), INTERVAL 2 DAY), NOW() - INTERVAL 3 DAY),

  ('a0000000-0000-4000-8000-000000000006', @mohamadou, 1,
   'Terrain 300 m² à Diamniadio',
   'Terrain titré, viabilisé, situé à proximité immédiate de la zone économique. Acte disponible.',
   24000000.00, 'vente', 4, 'Diamniadio', '+221 76 234 56 78', 210,
   'en_attente', NULL, NOW() - INTERVAL 3 DAY),

  ('a0000000-0000-4000-8000-000000000007', @mohamadou, 2,
   'Scooter électrique neuf',
   'Scooter électrique jamais utilisé, autonomie annoncée de 60 km. Chargeur inclus.',
   650000.00, 'vente', 5, 'Centre-ville', '+221 76 234 56 78', 312,
   'suspendue', NULL, NOW() - INTERVAL 4 DAY),

  ('a0000000-0000-4000-8000-000000000008', @yaya, 7,
   'Cours particuliers de mathématiques',
   'Professeur expérimenté, accompagnement des élèves du collège et du lycée, à domicile ou en ligne.',
   15000.00, 'vente', 6, 'Sor', '+221 77 123 45 67', 468,
   'active', DATE_ADD(CURDATE(), INTERVAL 60 DAY), NOW() - INTERVAL 5 DAY),

  ('a0000000-0000-4000-8000-000000000009', @yaya, 8,
   'Don de livres scolaires',
   'Lot de manuels scolaires en bon état, niveaux 6e à 3e. À retirer sur place, gratuitement.',
   NULL, 'don', 1, 'Sacré-Cœur', '+221 77 123 45 67', 95,
   'active', DATE_ADD(CURDATE(), INTERVAL 15 DAY), NOW() - INTERVAL 6 DAY),

  ('a0000000-0000-4000-8000-000000000010', @mohamadou, 1,
   'Recherche appartement 2 pièces meublé',
   'Recherche un appartement meublé de 2 pièces, quartier calme, budget jusqu''à 200 000 FCFA par mois.',
   NULL, 'recherche', 1, 'Point E', '+221 76 234 56 78', 42,
   'en_attente', NULL, NOW() - INTERVAL 7 DAY),

  ('a0000000-0000-4000-8000-000000000011', @yaya, 6,
   'Climatiseur split 1,5 CV',
   'Climatiseur réversible, installé il y a un an, encore sous garantie. Déplacement possible.',
   285000.00, 'vente', 1, 'Grand Yoff', '+221 77 123 45 67', 176,
   'active', DATE_ADD(CURDATE(), INTERVAL 25 DAY), NOW() - INTERVAL 8 DAY),

  ('a0000000-0000-4000-8000-000000000012', @mohamadou, 6,
   'Machine à coudre industrielle',
   'Machine à coudre professionnelle, utilisée en atelier, fonctionne parfaitement.',
   320000.00, 'vente', 5, 'Thiès', '+221 76 234 56 78', 121,
   'expirée', DATE_SUB(CURDATE(), INTERVAL 5 DAY), NOW() - INTERVAL 9 DAY)
ON DUPLICATE KEY UPDATE
  `user_id` = VALUES(`user_id`),
  `categorie_id` = VALUES(`categorie_id`),
  `titre` = VALUES(`titre`),
  `description` = VALUES(`description`),
  `prix` = VALUES(`prix`),
  `type_annonce` = VALUES(`type_annonce`),
  `ville_id` = VALUES(`ville_id`),
  `quartier` = VALUES(`quartier`),
  `telephone` = VALUES(`telephone`),
  `nb_vues` = VALUES(`nb_vues`),
  `status` = VALUES(`status`),
  `expire_at` = VALUES(`expire_at`),
  `created_at` = VALUES(`created_at`);

-- ============================================
-- 4. FAVORIS (8 lignes)
-- ============================================
-- Clé primaire composite (user_id, annonce_id) : l'unicité est assurée par
-- la base, ce qui rend le seed rejouable sans doublon possible.
INSERT INTO `favoris` (`user_id`, `annonce_id`, `created_at`) VALUES
  (@yaya,      'a0000000-0000-4000-8000-000000000002', NOW() - INTERVAL 2 DAY),
  (@yaya,      'a0000000-0000-4000-8000-000000000006', NOW() - INTERVAL 3 DAY),
  (@yaya,      'a0000000-0000-4000-8000-000000000007', NOW() - INTERVAL 4 DAY),
  (@yaya,      'a0000000-0000-4000-8000-000000000012', NOW() - INTERVAL 5 DAY),
  (@mohamadou, 'a0000000-0000-4000-8000-000000000001', NOW() - INTERVAL 1 DAY),
  (@mohamadou, 'a0000000-0000-4000-8000-000000000004', NOW() - INTERVAL 2 DAY),
  (@mohamadou, 'a0000000-0000-4000-8000-000000000008', NOW() - INTERVAL 5 DAY),
  (@mohamadou, 'a0000000-0000-4000-8000-000000000011', NOW() - INTERVAL 6 DAY)
ON DUPLICATE KEY UPDATE
  `created_at` = VALUES(`created_at`);

-- ============================================
-- 5. SIGNALEMENTS (7 lignes, UUID constants)
-- ============================================
-- Répartition : 4 en_attente, 2 traite, 1 rejete
-- `resolved_by` pointe vers le compte `moderateur` existant (moussa).
-- Les signalements non traités ont resolved_at = NULL et resolved_by = NULL,
-- conformément au schéma (colonnes nullables).

SET @moussa := 'e404c814-b6c8-11f1-a055-f430b9a2c03b';

INSERT INTO `signalements`
  (`id`, `user_id`, `annonce_id`, `raison`, `status`, `created_at`, `resolved_at`, `resolved_by`)
VALUES
  ('b0000000-0000-4000-8000-000000000001', @yaya, 'a0000000-0000-4000-8000-000000000003',
   'Prix trompeur', 'en_attente', NOW() - INTERVAL 20 MINUTE, NULL, NULL),

  ('b0000000-0000-4000-8000-000000000002', @yaya, 'a0000000-0000-4000-8000-000000000006',
   'Annonce en doublon', 'en_attente', NOW() - INTERVAL 1 HOUR, NULL, NULL),

  ('b0000000-0000-4000-8000-000000000003', @yaya, 'a0000000-0000-4000-8000-000000000007',
   'Contenu inapproprié', 'en_attente', NOW() - INTERVAL 3 HOUR, NULL, NULL),

  ('b0000000-0000-4000-8000-000000000004', @yaya, 'a0000000-0000-4000-8000-000000000012',
   'Vendeur injoignable', 'en_attente', NOW() - INTERVAL 1 DAY, NULL, NULL),

  ('b0000000-0000-4000-8000-000000000005', @yaya, 'a0000000-0000-4000-8000-000000000002',
   'Prix trompeur', 'traite', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 4 HOUR, @moussa),

  ('b0000000-0000-4000-8000-000000000006', @mohamadou, 'a0000000-0000-4000-8000-000000000001',
   'Annonce en doublon', 'traite', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, @moussa),

  ('b0000000-0000-4000-8000-000000000007', @mohamadou, 'a0000000-0000-4000-8000-000000000011',
   'Contenu inapproprié', 'rejete', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, @moussa)
ON DUPLICATE KEY UPDATE
  `user_id` = VALUES(`user_id`),
  `annonce_id` = VALUES(`annonce_id`),
  `raison` = VALUES(`raison`),
  `status` = VALUES(`status`),
  `created_at` = VALUES(`created_at`),
  `resolved_at` = VALUES(`resolved_at`),
  `resolved_by` = VALUES(`resolved_by`);

-- ============================================
-- 6. MESSAGES (16 lignes → 4 conversations)
-- ============================================
-- AUCUNE table « conversation » n'existe : une conversation est un couple
-- (annonce, interlocuteur), reconstruit par regroupement SQL.
-- `lu` = 1 signifie « lu par le destinataire ».
-- Les comptes de test auront ainsi 4 messages non lus pour yaya, 0 pour
-- Mohamadou.

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `annonces_id`, `contenu`, `lu`, `created_at`) VALUES
  -- Conversation A — annonce 001 (appartement, propriétaire : yaya)
  ('c0000000-0000-4000-8000-000000000001', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000001', 'Bonjour, l''appartement est-il toujours disponible ?', 1, NOW() - INTERVAL 73 HOUR),
  ('c0000000-0000-4000-8000-000000000002', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000001', 'Bonjour, oui il est toujours disponible.', 1, NOW() - INTERVAL 72 HOUR),
  ('c0000000-0000-4000-8000-000000000003', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000001', 'Parfait, je peux visiter samedi matin ?', 0, NOW() - INTERVAL 71 HOUR),
  ('c0000000-0000-4000-8000-000000000004', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000001', 'Samedi 10 h, c''est noté.', 1, NOW() - INTERVAL 70 HOUR),
  -- Conversation B — annonce 002 (Toyota RAV4, propriétaire : Mohamadou)
  ('c0000000-0000-4000-8000-000000000005', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000002', 'Bonjour, le RAV4 est-il négociable ?', 1, NOW() - INTERVAL 49 HOUR),
  ('c0000000-0000-4000-8000-000000000006', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000002', 'Bonjour, une légère marge est possible.', 1, NOW() - INTERVAL 48 HOUR),
  ('c0000000-0000-4000-8000-000000000007', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000002', 'Je propose 17 500 000 FCFA.', 1, NOW() - INTERVAL 47 HOUR),
  ('c0000000-0000-4000-8000-000000000008', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000002', 'Je peux accepter 18 000 000 FCFA.', 0, NOW() - INTERVAL 46 HOUR),
  ('c0000000-0000-4000-8000-000000000016', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000002', 'Entendu pour 18 000 000 FCFA.', 1, NOW() - INTERVAL 45 HOUR),
  -- Conversation C — annonce 004 (MacBook, propriétaire : yaya)
  ('c0000000-0000-4000-8000-000000000009', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000004', 'Le MacBook est-il encore sous garantie ?', 1, NOW() - INTERVAL 25 HOUR),
  ('c0000000-0000-4000-8000-000000000010', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000004', 'Oui, jusqu''en mars prochain.', 1, NOW() - INTERVAL 24 HOUR),
  ('c0000000-0000-4000-8000-000000000011', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000004', 'Je passe le prendre demain.', 0, NOW() - INTERVAL 23 HOUR),
  -- Conversation D — annonce 006 (terrain, propriétaire : Mohamadou)
  ('c0000000-0000-4000-8000-000000000012', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000006', 'Le terrain dispose-t-il d''un titre foncier ?', 1, NOW() - INTERVAL 6 HOUR),
  ('c0000000-0000-4000-8000-000000000013', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000006', 'Oui, acte et titre disponibles.', 1, NOW() - INTERVAL 5 HOUR),
  ('c0000000-0000-4000-8000-000000000014', @yaya,      @mohamadou, 'a0000000-0000-4000-8000-000000000006', 'Merci, je reviens vers vous rapidement.', 1, NOW() - INTERVAL 4 HOUR),
  ('c0000000-0000-4000-8000-000000000015', @mohamadou, @yaya,      'a0000000-0000-4000-8000-000000000006', 'Bien noté, à votre disposition.', 0, NOW() - INTERVAL 3 HOUR)
ON DUPLICATE KEY UPDATE
  `sender_id` = VALUES(`sender_id`),
  `receiver_id` = VALUES(`receiver_id`),
  `annonces_id` = VALUES(`annonces_id`),
  `contenu` = VALUES(`contenu`),
  `lu` = VALUES(`lu`),
  `created_at` = VALUES(`created_at`);

-- ============================================
-- 7. AUDIT_LOGS (12 lignes, UUID constants)
-- ============================================
-- `user_id` est NULLABLE (FK ON DELETE SET NULL) : une entrée est laissée
-- SANS auteur afin de reproduire ce cas réel et de vérifier que la lecture
-- utilise bien un LEFT JOIN (l'entrée orpheline doit rester visible).
-- `target_type` est un varchar libre, catégorisé ici sur les valeurs
-- utilisées par les écrans : annonce, signalement, utilisateur, categorie.

SET @souleymane := 'e3d65021-b6c8-11f1-a055-f430b9a2c03b';

INSERT INTO `audit_logs`
  (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`, `target_type`)
VALUES
  ('d0000000-0000-4000-8000-000000000001', @moussa, 'Annonce approuvée',
   'Climatiseur split 1,5 CV', '196.207.0.12', NOW() - INTERVAL 40 MINUTE, 'annonce'),

  ('d0000000-0000-4000-8000-000000000002', @moussa, 'Signalement traité',
   'Prix trompeur sur Toyota RAV4 2019 — Très bon état', '196.207.0.12', NOW() - INTERVAL 4 HOUR, 'signalement'),

  ('d0000000-0000-4000-8000-000000000003', @moussa, 'Annonce suspendue',
   'Scooter électrique neuf', '196.207.0.12', NOW() - INTERVAL 9 HOUR, 'annonce'),

  ('d0000000-0000-4000-8000-000000000004', @moussa, 'Signalement rejeté',
   'Contenu inapproprié sur Climatiseur split 1,5 CV', '196.207.0.12', NOW() - INTERVAL 22 HOUR, 'signalement'),

  ('d0000000-0000-4000-8000-000000000005', @souleymane, 'Rôle modifié',
   'moussa pouye — attribution du rôle moderateur', '196.207.0.45', NOW() - INTERVAL 26 HOUR, 'utilisateur'),

  ('d0000000-0000-4000-8000-000000000006', @moussa, 'Signalement traité',
   'Annonce en doublon sur Appartement 3 pièces à Almadies', '196.207.0.12', NOW() - INTERVAL 28 HOUR, 'signalement'),

  ('d0000000-0000-4000-8000-000000000007', @souleymane, 'Catégorie renommée',
   'Électronique', '196.207.0.45', NOW() - INTERVAL 30 HOUR, 'categorie'),

  ('d0000000-0000-4000-8000-000000000008', @moussa, 'Annonce approuvée',
   'Toyota RAV4 2019 — Très bon état', '196.207.0.12', NOW() - INTERVAL 34 HOUR, 'annonce'),

  ('d0000000-0000-4000-8000-000000000009', @moussa, 'Compte consulté',
   'yaya Diallo', '196.207.0.12', NOW() - INTERVAL 38 HOUR, 'utilisateur'),

  ('d0000000-0000-4000-8000-000000000010', @souleymane, 'Catégorie désactivée',
   'Autres', '196.207.0.45', NOW() - INTERVAL 44 HOUR, 'categorie'),

  ('d0000000-0000-4000-8000-000000000011', @souleymane, 'Compte consulté',
   'Mohamadou Manga', '196.207.0.45', NOW() - INTERVAL 50 HOUR, 'utilisateur'),

  ('d0000000-0000-4000-8000-000000000012', NULL, 'Annonce expirée automatiquement',
   'Machine à coudre industrielle — expiration de la durée de publication', NULL, NOW() - INTERVAL 3 DAY, 'annonce')
ON DUPLICATE KEY UPDATE
  `user_id` = VALUES(`user_id`),
  `action` = VALUES(`action`),
  `description` = VALUES(`description`),
  `ip_address` = VALUES(`ip_address`),
  `created_at` = VALUES(`created_at`),
  `target_type` = VALUES(`target_type`);

-- ============================================
-- VOLUMÉTRIE ATTENDUE APRÈS EXÉCUTION
-- ============================================
--   categories    :  9      annonces     : 12
--   villes        : 14      photos       :  0  (aucune : stockage protégé)
--   favoris       :  8      messages     : 16
--   signalements  :  7      audit_logs   : 12
--   users         :  4  ← INCHANGÉ (aucune écriture sur cette table)

-- ============================================
-- REMISE À ZÉRO (décommenter pour supprimer le jeu de démonstration)
-- ============================================
-- Les identifiants ci-dessous sont ceux du seed : seules les lignes de
-- démonstration sont visées. `photos`, `favoris`, `messages` et
-- `signalements` disparaissent automatiquement (FK ON DELETE CASCADE) lors
-- de la suppression des annonces. La table `users` n'est JAMAIS touchée.
--
-- DELETE FROM `audit_logs`   WHERE `id` LIKE 'd0000000-0000-4000-8000-%';
-- DELETE FROM `signalements` WHERE `id` LIKE 'b0000000-0000-4000-8000-%';
-- DELETE FROM `messages`     WHERE `id` LIKE 'c0000000-0000-4000-8000-%';
-- DELETE FROM `favoris`      WHERE `annonce_id` LIKE 'a0000000-0000-4000-8000-%';
-- DELETE FROM `annonces`     WHERE `id` LIKE 'a0000000-0000-4000-8000-%';
-- DELETE FROM `villes`       WHERE `id` BETWEEN 1 AND 14;
-- DELETE FROM `categories`   WHERE `id` BETWEEN 1 AND 9;