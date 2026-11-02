DROP INDEX idx_ingestion_jobs_espace ON ingestion_jobs;
ALTER TABLE ingestion_jobs
    DROP COLUMN confidentialite,
    DROP COLUMN espace_id;
