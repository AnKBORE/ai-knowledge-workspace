-- Adhésions : rôle local par espace, daté (jamais de suppression physique).

CREATE TABLE IF NOT EXISTS adhesion (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tenant_id INT UNSIGNED NOT NULL DEFAULT 1,
    espace_id BIGINT UNSIGNED NOT NULL,
    utilisateur_id BIGINT UNSIGNED NOT NULL,
    role_local ENUM('lecteur', 'contributeur', 'animateur', 'responsable') NOT NULL DEFAULT 'lecteur',
    debut_le DATE NOT NULL DEFAULT (CURRENT_DATE),
    fin_le DATE NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_adhesion (espace_id, utilisateur_id),
    KEY idx_adhesion_utilisateur (utilisateur_id),
    CONSTRAINT fk_adhesion_espace FOREIGN KEY (espace_id)
        REFERENCES espace (id) ON DELETE CASCADE,
    CONSTRAINT fk_adhesion_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
