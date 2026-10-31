-- Corrections: a human-corrected version of a document, tagged with its language.
-- Each correction also stores the original transcript, so the pair
-- (source -> corrected) becomes a reusable style example.

CREATE TABLE correction_document (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    version_document_id BIGINT UNSIGNED NULL,
    langue VARCHAR(5) NOT NULL DEFAULT 'fr',
    texte_source LONGTEXT NULL,
    texte_corrige LONGTEXT NOT NULL,
    auteur_id BIGINT UNSIGNED NULL,
    statut ENUM('valide', 'archive') NOT NULL DEFAULT 'valide',
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_correction_document (document_id),
    KEY idx_correction_langue (langue, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
