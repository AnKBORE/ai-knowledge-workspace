<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Progressive brute-force protection, keyed by a hash of the normalised email.
 *
 * The raw email is never stored in this table.
 */
final class TentativeConnexion
{
    public const MAX_ECHECS = 5;
    public const LOCK_MINUTES = 15;

    public function __construct(private readonly PDO $db)
    {
    }

    public function isLocked(string $courriel): bool
    {
        $row = $this->row($courriel);

        if ($row === null || $row['verrouille_jusqua'] === null) {
            return false;
        }

        return (int) strtotime((string) $row['verrouille_jusqua']) > time();
    }

    public function recordFailure(string $courriel): void
    {
        $empreinte = $this->empreinte($courriel);
        $row = $this->row($courriel);
        $echecs = (int) ($row['echecs'] ?? 0) + 1;
        $verrouille = $echecs >= self::MAX_ECHECS
            ? date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60)
            : null;

        if ($row === null) {
            $this->db->prepare(
                'INSERT INTO tentative_connexion (empreinte_courriel, echecs, verrouille_jusqua)
                 VALUES (:empreinte, :echecs, :verrouille)'
            )->execute([
                'empreinte' => $empreinte,
                'echecs' => $echecs,
                'verrouille' => $verrouille,
            ]);

            return;
        }

        $this->db->prepare(
            'UPDATE tentative_connexion
             SET echecs = :echecs, verrouille_jusqua = :verrouille
             WHERE empreinte_courriel = :empreinte'
        )->execute([
            'empreinte' => $empreinte,
            'echecs' => $echecs,
            'verrouille' => $verrouille,
        ]);
    }

    public function reset(string $courriel): void
    {
        $this->db->prepare('DELETE FROM tentative_connexion WHERE empreinte_courriel = :empreinte')
            ->execute(['empreinte' => $this->empreinte($courriel)]);
    }

    private function empreinte(string $courriel): string
    {
        return hash('sha256', strtolower(trim($courriel)));
    }

    /** @return array<string, mixed>|null */
    private function row(string $courriel): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT echecs, verrouille_jusqua FROM tentative_connexion WHERE empreinte_courriel = :empreinte LIMIT 1'
        );
        $stmt->execute(['empreinte' => $this->empreinte($courriel)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
