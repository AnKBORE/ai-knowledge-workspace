ALTER TABLE documents
    DROP FOREIGN KEY fk_documents_espace,
    DROP FOREIGN KEY fk_documents_proprietaire;

ALTER TABLE documents
    DROP COLUMN tenant_id,
    DROP COLUMN espace_id,
    DROP COLUMN resume,
    DROP COLUMN type,
    DROP COLUMN langue,
    DROP COLUMN confidentialite,
    DROP COLUMN statut,
    DROP COLUMN proprietaire_id,
    DROP COLUMN supprime_le;
