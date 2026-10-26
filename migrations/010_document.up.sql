-- Enrichissement de la table `documents` (créée en 001) avec les métadonnées
-- de la couche documentaire : espace, propriétaire, confidentialité, statut.

ALTER TABLE documents
    ADD COLUMN tenant_id INT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN espace_id BIGINT UNSIGNED NULL,
    ADD COLUMN resume TEXT NULL,
    ADD COLUMN type ENUM('guide', 'rapport', 'modele', 'presentation', 'autre') NOT NULL DEFAULT 'autre',
    ADD COLUMN langue VARCHAR(5) NOT NULL DEFAULT 'fr',
    ADD COLUMN confidentialite ENUM('public_interne', 'restreint', 'prive') NOT NULL DEFAULT 'restreint',
    ADD COLUMN statut ENUM('brouillon', 'publie', 'archive') NOT NULL DEFAULT 'publie',
    ADD COLUMN proprietaire_id BIGINT UNSIGNED NULL,
    ADD COLUMN supprime_le DATETIME NULL;

ALTER TABLE documents
    ADD CONSTRAINT fk_documents_espace FOREIGN KEY (espace_id)
        REFERENCES espace (id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_documents_proprietaire FOREIGN KEY (proprietaire_id)
        REFERENCES utilisateur (id) ON DELETE SET NULL;
