-- Revert the RAG pipeline schema.

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS document_chunks;
DROP TABLE IF EXISTS documents;
