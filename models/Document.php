<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Document metadata (the content itself lives in immutable `version_document`
 * rows). Deletion is logical.
 */
final class Document
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, filename, mime_type, espace_id, proprietaire_id, type, statut, confidentialite
             FROM documents
             WHERE id = :id AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function create(
        string $titre,
        ?int $espaceId,
        ?int $proprietaireId,
        string $filename,
        string $mimeType,
        string $type = 'autre',
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO documents (title, filename, mime_type, espace_id, proprietaire_id, type)
             VALUES (:title, :filename, :mime, :espace, :proprietaire, :type)'
        );
        $stmt->execute([
            'title' => $titre,
            'filename' => $filename,
            'mime' => $mimeType,
            'espace' => $espaceId,
            'proprietaire' => $proprietaireId,
            'type' => $type,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function listRecent(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.id, d.title, d.filename, d.statut,
                    (SELECT COUNT(*) FROM version_document v WHERE v.document_id = d.id) AS versions
             FROM documents d
             WHERE d.supprime_le IS NULL
             ORDER BY d.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDelete(int $id): void
    {
        $this->db->prepare('UPDATE documents SET supprime_le = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
