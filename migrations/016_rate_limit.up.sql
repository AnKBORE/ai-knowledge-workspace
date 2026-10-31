-- Simple fixed-window rate limiter (per user / per action).

CREATE TABLE rate_limit (
    cle VARCHAR(160) NOT NULL,
    compteur INT UNSIGNED NOT NULL DEFAULT 0,
    fenetre_debut INT UNSIGNED NOT NULL,
    PRIMARY KEY (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
