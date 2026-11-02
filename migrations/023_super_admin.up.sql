-- The seeded installation administrator becomes the protected Super Admin:
-- the account can never be deleted, suspended or demoted, but its profile
-- (name, e-mail, password) stays editable by its owner from /mon-profil.

ALTER TABLE utilisateur ADD COLUMN compte_super TINYINT(1) NOT NULL DEFAULT 0 AFTER statut;
CREATE INDEX idx_utilisateur_compte_super ON utilisateur (compte_super);

-- Flag the earliest living technical administrator (the seeded account).
UPDATE utilisateur
SET compte_super = 1
WHERE compte_super = 0
  AND id = (
    SELECT id FROM (
        SELECT MIN(id) AS id FROM utilisateur
        WHERE role_global = 'administrateur_technique' AND supprime_le IS NULL
    ) AS first_admin
);
