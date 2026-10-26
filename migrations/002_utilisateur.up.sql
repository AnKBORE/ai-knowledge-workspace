-- Comptes globaux de l'installation (mono-client, tenant_id figé à 1).
-- Suppression logique uniquement : aucune suppression physique.

CREATE TABLE IF NOT EXISTS utilisateur (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL DEFAULT 1,
    courriel VARCHAR(191) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role_global ENUM('membre', 'administrateur_fonctionnel', 'administrateur_technique') NOT NULL DEFAULT 'membre',
    statut ENUM('invitation', 'actif', 'suspendu', 'archive') NOT NULL DEFAULT 'actif',
    derniere_connexion_le DATETIME NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    supprime_le DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_utilisateur_courriel (courriel),
    KEY idx_utilisateur_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
