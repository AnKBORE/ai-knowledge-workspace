-- Mooré lexical bank: SIL dictionary entries + parallel Bible corpus
CREATE TABLE IF NOT EXISTS moore_lexicon (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    headword VARCHAR(120) NOT NULL,
    variant VARCHAR(120) NULL,
    pos VARCHAR(40) NULL,
    gloss_fr TEXT NULL,
    gloss_en TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_head (headword),
    FULLTEXT KEY ft_gloss (gloss_fr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moore_corpus (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    source VARCHAR(60) NOT NULL DEFAULT 'bible',
    ref VARCHAR(40) NULL,
    moore_text TEXT NOT NULL,
    french_text TEXT NULL,
    english_text TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_source (source),
    FULLTEXT KEY ft_moore (moore_text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
