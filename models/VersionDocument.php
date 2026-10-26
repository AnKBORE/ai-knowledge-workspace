<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Immutable document versions: every deposit inserts a new row, never an
 * update. `numero` is unique per document and `empreinte` is the SHA-256 of
 * the stored file.
 */
final class VersionDocument
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(
        int $documentId,
        string $fichier,
        string $nomOriginal,
        string $typeMime,
        int $tailleOctets,
        string $empreinte,
        ?int $auteurId,
        ?string $commentaire = null,
    ): int {
        $numero = $this->nextNumero($documentId);

        $stmt = $this->db->prepare(
            'INSERT INTO version_document
                (document_id, numero, fichier, nom_original, type_mime, taille_octets, empreinte, auteur_id, commentaire)
             VALUES
                (:document, :numero, :fichier, :nom, :mime, :taille, :empreinte, :auteur, :commentaire)'
        );
        $stmt->execute([
            'document' => $documentId,
            'numero' => $numero,
            'fichier' => $fichier,
            'nom' => $nomOriginal,
            'mime' => $typeMime,
            'taille' => $tailleOctets,
            'empreinte' => $empreinte,
            'auteur' => $auteurId,
            'commentaire' => $commentaire,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function nextNumero(int $documentId): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM version_document WHERE document_id = :document');
        $stmt->execute(['document' => $documentId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public function listForDocument(int $documentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, numero, fichier, nom_original, type_mime, taille_octets, empreinte, auteur_id, commentaire, cree_le
             FROM version_document
             WHERE document_id = :document
             ORDER BY numero DESC'
        );
        $stmt->execute(['document' => $documentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string, mixed>|null */
    public function latest(int $documentId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, numero, fichier, empreinte FROM version_document
             WHERE document_id = :document
             ORDER BY numero DESC LIMIT 1'
        );
        $stmt->execute(['document' => $documentId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
