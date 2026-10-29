<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Background ingestion queue (slow uploads processed by tools/ingest_worker.php).
 */
final class IngestionJob
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * @param array{utilisateur_id: int, titre: string, categorie_id: int, nom_original: string, type_mime: string, taille_octets: int, chemin_tmp: string, langue: string} $job
     */
    public function create(array $job): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ingestion_jobs (utilisateur_id, titre, categorie_id, nom_original, type_mime, taille_octets, chemin_tmp, langue)
             VALUES (:utilisateur_id, :titre, :categorie_id, :nom_original, :type_mime, :taille_octets, :chemin_tmp, :langue)'
        );
        $stmt->execute($job);

        return (int) $this->db->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT *, TIMESTAMPDIFF(SECOND, cree_le, NOW()) AS elapsed FROM ingestion_jobs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Atomically claim a pending job, so two workers never process it twice.
     */
    public function claim(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE ingestion_jobs SET statut = 'en_cours' WHERE id = :id AND statut = 'en_attente'");
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() === 1;
    }

    public function complete(int $id, int $documentId, int $chunks): void
    {
        $stmt = $this->db->prepare(
            "UPDATE ingestion_jobs SET statut = 'termine', document_id = :document_id, chunks = :chunks, erreur = NULL WHERE id = :id"
        );
        $stmt->execute(['id' => $id, 'document_id' => $documentId, 'chunks' => $chunks]);
    }

    public function fail(int $id, string $error): void
    {
        $stmt = $this->db->prepare("UPDATE ingestion_jobs SET statut = 'echec', erreur = :erreur WHERE id = :id");
        $stmt->execute(['id' => $id, 'erreur' => mb_substr($error, 0, 500)]);
    }
}
