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

    /**
     * Every active space, for the administration page.
     *
     * @return list<array<string, mixed>>
     */

    /**
     * Spaces the user may enter: an active membership, an internal public
     * space, or every space for a global administrator.
     *
     * @return list<array<string, mixed>>
     */
    public function pourUtilisateur(int $userId, bool $admin = false): array
    {
        $sql = 'SELECT e.id, e.nom, e.visibilite, e.statut,
                       (SELECT COUNT(DISTINCT d.id) FROM documents d
                          JOIN document_espace de ON de.document_id = d.id
                         WHERE de.espace_id = e.id AND d.supprime_le IS NULL)
                       + (SELECT COUNT(*) FROM documents d2
                           WHERE d2.espace_id = e.id AND d2.supprime_le IS NULL
                             AND NOT EXISTS (SELECT 1 FROM document_espace x
                                             WHERE x.document_id = d2.id)) AS documents
                FROM espace e
                WHERE e.supprime_le IS NULL';
        $params = [];

        if (!$admin) {
            $sql .= " AND (e.visibilite = 'public_interne'
                OR EXISTS (SELECT 1 FROM adhesion a
                           WHERE a.espace_id = e.id AND a.utilisateur_id = :uid
                             AND a.debut_le <= CURRENT_DATE
                             AND (a.fin_le IS NULL OR a.fin_le >= CURRENT_DATE)))";
            $params['uid'] = $userId;
        }

        $sql .= ' ORDER BY e.nom';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, nom, visibilite, statut, parent_id, cree_le
             FROM espace
             WHERE supprime_le IS NULL
             ORDER BY id'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
