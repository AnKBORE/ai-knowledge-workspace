-- Jetons temporaires à usage unique (activation / réinitialisation).
-- Seule l'empreinte SHA-256 est stockée, jamais le jeton brut.

CREATE TABLE IF NOT EXISTS jeton_compte (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id BIGINT UNSIGNED NOT NULL,
    usage_jeton ENUM('activation', 'reinitialisation') NOT NULL,
    empreinte CHAR(64) NOT NULL,
    expire_le DATETIME NOT NULL,
    utilise_le DATETIME NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_jeton_empreinte (empreinte),
    KEY idx_jeton_utilisateur (utilisateur_id),
    CONSTRAINT fk_jeton_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
