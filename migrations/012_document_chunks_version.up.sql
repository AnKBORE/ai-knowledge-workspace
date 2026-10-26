-- Rattachement optionnel des chunks à la version qui les a produits.

ALTER TABLE document_chunks
    ADD COLUMN version_document_id BIGINT UNSIGNED NULL;

ALTER TABLE document_chunks
    ADD CONSTRAINT fk_chunks_version FOREIGN KEY (version_document_id)
        REFERENCES version_document (id) ON DELETE SET NULL;
