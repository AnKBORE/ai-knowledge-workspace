-- Cache of per-segment document translations (live audio translation, exports)
CREATE TABLE IF NOT EXISTS document_translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    version_document_id BIGINT UNSIGNED NOT NULL,
    lang VARCHAR(8) NOT NULL,
    segment_index INT UNSIGNED NOT NULL,
    translation MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doc_translation (document_id, version_document_id, lang, segment_index),
    KEY idx_doc_translations_doc (document_id),
    CONSTRAINT fk_doc_translations_document FOREIGN KEY (document_id) REFERENCES documents (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
