-- A document can belong to several workspaces: the workspace is a filing and
-- access dimension, not a property of the document. `documents.espace_id` stays
-- as the primary workspace (display, compatibility) and is mirrored here.
CREATE TABLE document_espace (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_id INT UNSIGNED NOT NULL,
    espace_id BIGINT UNSIGNED NOT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_document_espace (document_id, espace_id),
    KEY idx_document_espace_espace (espace_id),
    CONSTRAINT fk_document_espace_document FOREIGN KEY (document_id) REFERENCES documents (id) ON DELETE CASCADE,
    CONSTRAINT fk_document_espace_espace FOREIGN KEY (espace_id) REFERENCES espace (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO document_espace (document_id, espace_id)
SELECT d.id, d.espace_id
FROM documents d
WHERE d.espace_id IS NOT NULL
  AND d.supprime_le IS NULL;
