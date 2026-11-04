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
            'SELECT id, title, filename, mime_type, espace_id, proprietaire_id, type, statut, confidentialite, categorie_id
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
        ?int $categorieId = null,
        string $confidentialite = 'restreint',
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO documents (title, filename, mime_type, espace_id, proprietaire_id, type, categorie_id, confidentialite)
             VALUES (:title, :filename, :mime, :espace, :proprietaire, :type, :categorie, :confidentialite)'
        );
        $stmt->execute([
            'title' => $titre,
            'filename' => $filename,
            'mime' => $mimeType,
            'espace' => $espaceId,
            'proprietaire' => $proprietaireId,
            'type' => $type,
            'categorie' => $categorieId,
            'confidentialite' => $confidentialite,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<int, true>|bool|null $allowed ids the actor may read
     *        (null or true = no restriction, [] = nothing visible)
     * @return list<array<string, mixed>>
     */
    /**
     * Replaces the whole set of workspaces attached to a document.
     *
     * @param list<int> $espaceIds
     */
    public function remplacerEspaces(int $documentId, array $espaceIds): void
    {
        $espaceIds = array_values(array_unique(array_map('intval', $espaceIds)));

        $this->db->beginTransaction();

        try {
            $this->db->prepare('DELETE FROM document_espace WHERE document_id = :id')
                ->execute(['id' => $documentId]);

            $insert = $this->db->prepare(
                'INSERT INTO document_espace (document_id, espace_id) VALUES (:document, :espace)'
            );

            foreach ($espaceIds as $espaceId) {
                $insert->execute(['document' => $documentId, 'espace' => $espaceId]);
            }

            // The primary workspace follows the first attached one.
            $this->db->prepare('UPDATE documents SET espace_id = :espace WHERE id = :id')
                ->execute(['espace' => $espaceIds[0] ?? null, 'id' => $documentId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }


    public function updateConfidentialite(int $id, string $confidentialite): void
    {
        $this->db->prepare('UPDATE documents SET confidentialite = :c WHERE id = :id')
            ->execute(['c' => $confidentialite, 'id' => $id]);
    }
    /** @return list<int> */
    public function espacesDe(int $documentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT espace_id FROM document_espace WHERE document_id = :id ORDER BY espace_id'
        );
        $stmt->execute(['id' => $documentId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function listRecent(int $limit = 20, int $offset = 0, array|bool|null $allowed = null): array
    {
        if (is_array($allowed) && $allowed === []) {
            return [];
        }

        $where = 'd.supprime_le IS NULL';

        if (is_array($allowed)) {
            $where .= ' AND d.id IN (' . implode(',', array_map('intval', array_keys($allowed))) . ')';
        }

        $stmt = $this->db->prepare(
            'SELECT d.id, d.title, d.filename, d.mime_type, d.langue, d.statut, d.categorie_id, d.espace_id,
                    c.nom AS categorie_nom, c.couleur AS categorie_couleur,
                    (SELECT COUNT(*) FROM version_document v WHERE v.document_id = d.id) AS versions,
                    (SELECT GROUP_CONCAT(de.espace_id ORDER BY de.espace_id) FROM document_espace de WHERE de.document_id = d.id) AS espaces
             FROM documents d
             LEFT JOIN categorie c ON c.id = d.categorie_id
             WHERE ' . $where . '
             ORDER BY d.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Change where a document lives and who may read it. Reserved to the
     * administration: the deposit decides the default, this is the override.
     */
    public function updateControle(int $id, ?int $espaceId, string $confidentialite): void
    {
        $stmt = $this->db->prepare(
            'UPDATE documents
             SET espace_id = :espace, confidentialite = :confidentialite
             WHERE id = :id AND supprime_le IS NULL'
        );
        $stmt->execute([
            'espace' => $espaceId,
            'confidentialite' => $confidentialite,
            'id' => $id,
        ]);
    }
    /** @return list<array<string, mixed>> */
    public function byCategorie(int $categorieId, int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.id, d.title, d.filename, d.mime_type, d.langue, d.statut, d.categorie_id, d.espace_id,
                    c.nom AS categorie_nom, c.couleur AS categorie_couleur,
                    (SELECT COUNT(*) FROM version_document v WHERE v.document_id = d.id) AS versions,
                    (SELECT GROUP_CONCAT(de.espace_id ORDER BY de.espace_id) FROM document_espace de WHERE de.document_id = d.id) AS espaces
             FROM documents d
             LEFT JOIN categorie c ON c.id = d.categorie_id
             WHERE d.supprime_le IS NULL AND d.categorie_id = :categorie
             ORDER BY d.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('categorie', $categorieId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int, true>|bool|null $allowed ids the actor may read
     * @return list<array<string, mixed>>
     */
    public function search(string $query, int $limit = 20, array|bool|null $allowed = null): array
    {
        if (is_array($allowed) && $allowed === []) {
            return [];
        }

        $like = '%' . $query . '%';
        $scope = is_array($allowed)
            ? ' AND d.id IN (' . implode(',', array_map('intval', array_keys($allowed))) . ')'
            : '';

        $stmt = $this->db->prepare(
            'SELECT d.id, d.title, d.filename, d.mime_type, d.langue, d.statut, d.categorie_id, d.espace_id,
                    c.nom AS categorie_nom, c.couleur AS categorie_couleur,
                    (SELECT COUNT(*) FROM version_document v WHERE v.document_id = d.id) AS versions,
                    (SELECT GROUP_CONCAT(de.espace_id ORDER BY de.espace_id) FROM document_espace de WHERE de.document_id = d.id) AS espaces
             FROM documents d
             LEFT JOIN categorie c ON c.id = d.categorie_id
             WHERE d.supprime_le IS NULL'
            . $scope . '
               AND (d.title LIKE :query OR d.filename LIKE :query)
             ORDER BY d.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('query', $like);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSearch(string $query): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM documents
             WHERE supprime_le IS NULL AND (title LIKE :query OR filename LIKE :query)'
        );
        $stmt->execute(['query' => '%' . $query . '%']);

        return (int) $stmt->fetchColumn();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL')->fetchColumn();
    }

    public function softDelete(int $id): void
    {
        $this->db->prepare('UPDATE documents SET supprime_le = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => $id]);

        // The chunks and versions are derived from the document: kept, they outlive it
        // and keep counting in totals that no page shows. The file itself stays in
        // storage/files under its SHA-256, which is what makes a document restorable.
        $this->db->prepare('DELETE FROM document_chunks WHERE document_id = :id')
            ->execute(['id' => $id]);
        $this->db->prepare('DELETE FROM version_document WHERE document_id = :id')
            ->execute(['id' => $id]);
    }
    /**
     * Record the detected language of the document body.
     */
    public function setLangue(int $documentId, string $langue): void
    {
        $stmt = $this->db->prepare('UPDATE documents SET langue = :langue WHERE id = :id');
        $stmt->execute(['langue' => $langue, 'id' => $documentId]);
    }

}
