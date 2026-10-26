-- Versions immuables d'un document : chaque dépôt crée une ligne, jamais une
-- mise à jour. Le numéro est unique par document et l'empreinte SHA-256
-- garantit l'intégrité du fichier stocké.

CREATE TABLE IF NOT EXISTS version_document (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id INT UNSIGNED NOT NULL,
    numero INT UNSIGNED NOT NULL DEFAULT 1,
    fichier VARCHAR(255) NOT NULL,
    nom_original VARCHAR(255) NOT NULL,
    type_mime VARCHAR(100) NOT NULL,
    taille_octets BIGINT UNSIGNED NOT NULL DEFAULT 0,
    empreinte CHAR(64) NOT NULL,
    auteur_id BIGINT UNSIGNED NULL,
    commentaire VARCHAR(500) NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_version_document_numero (document_id, numero),
    KEY idx_version_auteur (auteur_id),
    CONSTRAINT fk_version_document FOREIGN KEY (document_id)
        REFERENCES documents (id) ON DELETE CASCADE,
    CONSTRAINT fk_version_auteur FOREIGN KEY (auteur_id)
        REFERENCES utilisateur (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
