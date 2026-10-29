-- Background ingestion queue: slow uploads (audio, images, scanned PDFs) are
-- processed by a detached worker so the HTTP request returns immediately.
-- The document row is only created once extraction succeeds, so retrieval and
-- listings never expose a half-processed document.

CREATE TABLE ingestion_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id BIGINT UNSIGNED NOT NULL,
    titre VARCHAR(255) NOT NULL,
    categorie_id INT UNSIGNED NULL,
    nom_original VARCHAR(255) NOT NULL,
    type_mime VARCHAR(120) NOT NULL DEFAULT '',
    taille_octets BIGINT UNSIGNED NOT NULL DEFAULT 0,
    chemin_tmp VARCHAR(500) NOT NULL,
    langue VARCHAR(5) NOT NULL DEFAULT 'en',
    statut ENUM('en_attente', 'en_cours', 'termine', 'echec') NOT NULL DEFAULT 'en_attente',
    erreur VARCHAR(500) NULL,
    document_id BIGINT UNSIGNED NULL,
    chunks INT UNSIGNED NULL,
    cree_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maj_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ingestion_jobs_user (utilisateur_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
