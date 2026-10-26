ALTER TABLE document_chunks
    DROP FOREIGN KEY fk_chunks_version;

ALTER TABLE document_chunks
    DROP COLUMN version_document_id;
