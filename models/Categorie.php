<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Document categories (auto-increment id, unique slug derived from the name).
 */
final class Categorie
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT c.id, c.nom, c.slug, c.description, c.couleur, c.cree_le,
                    (SELECT COUNT(*) FROM documents d WHERE d.categorie_id = c.id AND d.supprime_le IS NULL) AS documents
             FROM categorie c
             ORDER BY c.nom ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nom, slug, description, couleur, cree_le FROM categorie WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(string $nom, ?string $description, string $couleur): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categorie (nom, slug, description, couleur) VALUES (:nom, :slug, :description, :couleur)'
        );
        $stmt->execute([
            'nom' => $nom,
            'slug' => $this->slugify($nom),
            'description' => $description,
            'couleur' => $couleur,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nom, ?string $description, string $couleur): void
    {
        $stmt = $this->db->prepare(
            'UPDATE categorie SET nom = :nom, slug = :slug, description = :description, couleur = :couleur WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'slug' => $this->slugify($nom),
            'description' => $description,
            'couleur' => $couleur,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM categorie WHERE id = :id')->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM categorie')->fetchColumn();
    }

    public function slugify(string $nom): string
    {
        // Portable transliteration (iconv //TRANSLIT is unreliable on Windows).
        $nom = strtr($nom, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n', 'ÿ' => 'y',
            'À' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Á' => 'a', 'Ã' => 'a', 'Å' => 'a',
            'È' => 'e', 'É' => 'e', 'Ê' => 'e', 'Ë' => 'e',
            'Ì' => 'i', 'Í' => 'i', 'Î' => 'i', 'Ï' => 'i',
            'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Ö' => 'o', 'Õ' => 'o',
            'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u',
            'Ç' => 'c', 'Ñ' => 'n', 'Ÿ' => 'y',
            'œ' => 'oe', 'æ' => 'ae',
        ]);

        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $nom));

        return trim($slug, '-') ?: 'categorie';
    }
}
