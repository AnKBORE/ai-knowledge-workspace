-- Historique des adhésions terminées (aucune suppression physique).

CREATE TABLE IF NOT EXISTS historique_adhesions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    adhesion_id BIGINT UNSIGNED NOT NULL,
    role_local ENUM('lecteur', 'contributeur', 'animateur', 'responsable') NOT NULL,
    debut_le DATE NOT NULL,
    fin_le DATE NOT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historique_adhesion (adhesion_id),
    CONSTRAINT fk_historique_adhesion FOREIGN KEY (adhesion_id)
        REFERENCES adhesion (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
