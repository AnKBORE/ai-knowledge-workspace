<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Append-only audit journal.
 *
 * A failed audit write must NEVER break the request: every error is swallowed.
 * Details are free-form JSON and must never contain secrets.
 */
final class JournalService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * @param array<string, mixed> $details
     */
    public function log(
        ?int $acteurId,
        string $action,
        ?string $objetType = null,
        ?int $objetId = null,
        string $resultat = 'succes',
        array $details = [],
        ?string $ip = null,
    ): void {
        try {
            $this->db->prepare(
                'INSERT INTO journal (acteur_id, action, objet_type, objet_id, resultat, adresse_ip, details)
                 VALUES (:acteur, :action, :type, :objet, :resultat, :ip, :details)'
            )->execute([
                'acteur' => $acteurId,
                'action' => $action,
                'type' => $objetType,
                'objet' => $objetId,
                'resultat' => $resultat,
                'ip' => $this->ip($ip),
                'details' => $details === []
                    ? null
                    : json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable) {
            // Audit is best-effort and must never break the request.
        }
    }

    private function ip(?string $ip): string
    {
        $ip ??= (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $ip = trim($ip);

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '0.0.0.0';
    }
}
