<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Human corrections attached to a document.
 *
 * A correction carries the corrected text, its language, and the original
 * transcript it replaces — that pair is what the learning layer reuses.
 */
final class Correction
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(
        int $documentId,
        string $langue,
        string $texteCorrige,
        ?string $texteSource = null,
        ?int $versionDocumentId = null,
        ?int $auteurId = null,
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO correction_document (document_id, version_document_id, langue, texte_source, texte_corrige, auteur_id)
             VALUES (:document_id, :version_id, :langue, :source, :corrige, :auteur)'
        );
        $stmt->execute([
            'document_id' => $documentId,
            'version_id' => $versionDocumentId,
            'langue' => $langue,
            'source' => $texteSource,
            'corrige' => $texteCorrige,
            'auteur' => $auteurId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return list<array<string, mixed>> */
    public function listForDocument(int $documentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, langue, texte_corrige, statut, cree_le
             FROM correction_document
             WHERE document_id = :id AND statut = \'valide\'
             ORDER BY id DESC'
        );
        $stmt->execute(['id' => $documentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Validated corrections of one language, most recent first.
     *
     * @return list<array<string, mixed>>
     */
    public function validForLangue(string $langue, int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, document_id, langue, texte_source, texte_corrige, cree_le
             FROM correction_document
             WHERE langue = :langue AND statut = \'valide\'
             ORDER BY id DESC
             LIMIT :limite'
        );
        $stmt->bindValue('langue', $langue);
        $stmt->bindValue('limite', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM correction_document')->fetchColumn();
    }
}
