-- Protection progressive contre les essais de connexion (anti-brute-force).

CREATE TABLE IF NOT EXISTS tentative_connexion (
    empreinte_courriel CHAR(64) NOT NULL,
    echecs INT UNSIGNED NOT NULL DEFAULT 0,
    verrouille_jusqua DATETIME NULL,
    maj_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (empreinte_courriel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
