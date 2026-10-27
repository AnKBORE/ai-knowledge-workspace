DROP INDEX idx_documents_categorie ON documents;
ALTER TABLE documents DROP FOREIGN KEY fk_documents_categorie;
ALTER TABLE documents DROP COLUMN categorie_id;
DROP TABLE categorie;
