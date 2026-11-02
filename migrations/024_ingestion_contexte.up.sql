-- Slow deposits (audio, images, scanned PDFs) are queued: the chosen workspace
-- and the chosen confidentiality must survive the queue, otherwise a document
-- would silently fall back to the first space on the worker side.
ALTER TABLE ingestion_jobs
    ADD COLUMN espace_id BIGINT UNSIGNED NULL AFTER categorie_id,
    ADD COLUMN confidentialite ENUM('public_interne', 'restreint', 'prive') NOT NULL DEFAULT 'restreint' AFTER espace_id;

CREATE INDEX idx_ingestion_jobs_espace ON ingestion_jobs (espace_id);
