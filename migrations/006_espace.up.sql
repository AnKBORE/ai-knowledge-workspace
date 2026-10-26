-- Espaces de travail (arbre via parent_id). `prive` coupe l'héritage des
-- rôles locaux.

CREATE TABLE IF NOT EXISTS espace (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL DEFAULT 1,
    parent_id BIGINT UNSIGNED NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    visibilite ENUM('public_interne', 'restreint', 'prive') NOT NULL DEFAULT 'restreint',
    statut ENUM('actif', 'archive') NOT NULL DEFAULT 'actif',
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    supprime_le DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_espace_parent (parent_id),
    CONSTRAINT fk_espace_parent FOREIGN KEY (parent_id)
        REFERENCES espace (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
