-- Categories for documents (auto-increment unique id, unique slug).

CREATE TABLE categorie (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    description VARCHAR(255) NULL,
    couleur VARCHAR(7) NOT NULL DEFAULT '#4a63c8',
    cree_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorie_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE documents ADD COLUMN categorie_id INT UNSIGNED NULL AFTER espace_id;
ALTER TABLE documents ADD CONSTRAINT fk_documents_categorie
    FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE SET NULL;
CREATE INDEX idx_documents_categorie ON documents (categorie_id);

INSERT INTO categorie (nom, slug, description, couleur) VALUES
    ('Rapports', 'rapports', 'Rapports d''activité, bilans et comptes rendus', '#17752d'),
    ('Procédures', 'procedures', 'Procédures, protocoles et règlements', '#4a63c8'),
    ('Formation', 'formation', 'Supports de formation et fiches pédagogiques', '#b8860b'),
    ('Administratif', 'administratif', 'Documents administratifs et courriers', '#7a4bb5');
