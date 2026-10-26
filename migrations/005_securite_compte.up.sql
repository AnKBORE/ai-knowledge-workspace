-- Sécurité du compte : secret MFA (optionnel) et empreinte de session
-- (permet d'invalider les sessions à la modification du mot de passe).

CREATE TABLE IF NOT EXISTS securite_compte (
    utilisateur_id BIGINT UNSIGNED NOT NULL,
    secret_mfa VARCHAR(255) NULL,
    empreinte_session CHAR(64) NULL,
    maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (utilisateur_id),
    CONSTRAINT fk_securite_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
