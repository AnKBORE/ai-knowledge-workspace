-- Journal d'audit en ajout seul : jamais de UPDATE ni de DELETE.
-- Cette table est aussi utilisée pour journaliser les refus de permission.

CREATE TABLE IF NOT EXISTS journal (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    acteur_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    objet_type VARCHAR(50) NULL,
    objet_id BIGINT UNSIGNED NULL,
    resultat ENUM('succes', 'refus', 'erreur') NOT NULL DEFAULT 'succes',
    adresse_ip VARCHAR(45) NOT NULL,
    details JSON NULL,
    horodatage DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_journal_horodatage (horodatage),
    KEY idx_journal_objet (objet_type, objet_id),
    KEY idx_journal_acteur (acteur_id),
    CONSTRAINT fk_journal_acteur FOREIGN KEY (acteur_id)
        REFERENCES utilisateur (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
