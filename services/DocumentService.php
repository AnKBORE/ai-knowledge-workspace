<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use App\Models\VersionDocument;
use RuntimeException;

/**
 * Document intake: validation, content-addressed storage, metadata and an
 * immutable version row per deposit.
 *
 * Files are stored OUTSIDE the web root, under a SHA-256 name (deduplicated).
 */
final class DocumentService
{
    public const MAX_SIZE = 20 * 1024 * 1024;

    /** @var list<string> */
    public const ALLOWED = [
        'pdf', 'txt', 'md',
        'png', 'jpg', 'jpeg', 'tif', 'tiff', 'webp',
        'mp3', 'wav', 'm4a', 'ogg', 'webm', 'aac', 'flac',
    ];

    public function __construct(
        private readonly Document $documents,
        private readonly VersionDocument $versions,
        private readonly string $storagePath,
    ) {
    }

    /**
     * @param array{tmp_name: string, name: string, type?: string, size?: int, error?: int} $upload
     * @return array{document_id: int, version_id: int, sha256: string, stored_path: string}
     */
    public function createDocument(string $titre, ?int $espaceId, ?int $proprietaireId, array $upload, ?int $categorieId = null): array
    {
        $this->validate($upload);
        [$sha, $stored] = $this->store($upload);

        $documentId = $this->documents->create(
            $titre,
            $espaceId,
            $proprietaireId,
            (string) $upload['name'],
            (string) ($upload['type'] ?? ''),
            'autre',
            $categorieId,
        );

        $versionId = $this->versions->create(
            $documentId,
            $stored,
            (string) $upload['name'],
            (string) ($upload['type'] ?? ''),
            (int) ($upload['size'] ?? 0),
            $sha,
            $proprietaireId,
        );

        return [
            'document_id' => $documentId,
            'version_id' => $versionId,
            'sha256' => $sha,
            'stored_path' => $stored,
        ];
    }

    /**
     * @param array{tmp_name: string, name: string, type?: string, size?: int, error?: int} $upload
     * @return array{document_id: int, version_id: int, sha256: string, stored_path: string}
     */
    public function addVersion(int $documentId, ?int $auteurId, array $upload): array
    {
        $this->validate($upload);
        [$sha, $stored] = $this->store($upload);

        $versionId = $this->versions->create(
            $documentId,
            $stored,
            (string) $upload['name'],
            (string) ($upload['type'] ?? ''),
            (int) ($upload['size'] ?? 0),
            $sha,
            $auteurId,
        );

        return [
            'document_id' => $documentId,
            'version_id' => $versionId,
            'sha256' => $sha,
            'stored_path' => $stored,
        ];
    }

    /**
     * @param array{tmp_name: string, name: string, type?: string, size?: int, error?: int} $upload
     * @return array{0: string, 1: string} SHA-256 and stored file name
     */
    private function store(array $upload): array
    {
        $tmp = (string) $upload['tmp_name'];
        $sha = (string) hash_file('sha256', $tmp);

        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0775, true);
        }

        $target = $this->storagePath . '/' . $sha;

        if (!is_file($target)) {
            if (!move_uploaded_file($tmp, $target) && !copy($tmp, $target)) {
                throw new RuntimeException('Could not store the file.');
            }
        }

        return [$sha, $sha];
    }

    /**
     * @param array{tmp_name: string, name: string, type?: string, size?: int, error?: int} $upload
     */
    private function validate(array $upload): void
    {
        if (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed.');
        }

        $name = (string) ($upload['name'] ?? '');
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED, true)) {
            throw new RuntimeException('File type not allowed.');
        }

        $size = (int) ($upload['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new RuntimeException('File size out of range.');
        }

        if (!is_file((string) ($upload['tmp_name'] ?? ''))) {
            throw new RuntimeException('Missing uploaded file.');
        }
    }
}
