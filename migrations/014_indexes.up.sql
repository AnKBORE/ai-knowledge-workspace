-- Index de performance pour les requêtes fréquentes.

CREATE INDEX idx_documents_espace ON documents (espace_id);
CREATE INDEX idx_documents_supprime ON documents (supprime_le);
CREATE INDEX idx_journal_action ON journal (action);
