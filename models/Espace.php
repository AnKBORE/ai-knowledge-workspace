<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Workspace spaces (tree via parent_id).
 *
 * `visibilite = prive` stops local-role inheritance.
 */
final class Espace
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tenant_id, parent_id, nom, description, visibilite, statut
             FROM espace
             WHERE id = :id AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(string $nom, ?int $parentId = null, string $visibilite = 'restreint'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO espace (parent_id, nom, visibilite) VALUES (:parent, :nom, :visibilite)'
        );
        $stmt->execute([
            'parent' => $parentId,
            'nom' => $nom,
            'visibilite' => $visibilite,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * First active space (used as the default space for uploaded documents).
     */
    public function firstActive(): ?int
    {
        $row = $this->db->query(
            "SELECT id FROM espace WHERE supprime_le IS NULL AND statut = 'actif' ORDER BY id ASC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : (int) $row['id'];
    }
}
