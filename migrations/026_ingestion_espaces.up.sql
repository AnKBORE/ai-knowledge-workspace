-- A queued deposit may target several workspaces: the list travels with the job
-- as JSON, and the worker attaches them when it finalises the document.
ALTER TABLE ingestion_jobs ADD COLUMN espace_ids JSON NULL AFTER espace_id;
