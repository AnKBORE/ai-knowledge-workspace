<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Fixed-window rate limiter backed by the `rate_limit` table.
 *
 * Protects the costly endpoints (ask, summarize, translate, live transcription)
 * against loops, stolen sessions and accidental click storms — not against the
 * language model budget, which is covered by the partner credits.
 */
final class RateLimiter
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Consume one unit for `$key` when under `$max` requests per window.
     */
    public function allow(string $key, int $max, int $windowSeconds): bool
    {
        $now = time();

        $stmt = $this->db->prepare('SELECT compteur, fenetre_debut FROM rate_limit WHERE cle = :cle');
        $stmt->execute(['cle' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            $this->db->prepare(
                'INSERT INTO rate_limit (cle, compteur, fenetre_debut) VALUES (:cle, 1, :debut)'
            )->execute(['cle' => $key, 'debut' => $now]);

            return true;
        }

        if ($now - (int) $row['fenetre_debut'] >= $windowSeconds) {
            $this->db->prepare(
                'UPDATE rate_limit SET compteur = 1, fenetre_debut = :debut WHERE cle = :cle'
            )->execute(['cle' => $key, 'debut' => $now]);

            return true;
        }

        if ((int) $row['compteur'] >= $max) {
            return false;
        }

        $this->db->prepare('UPDATE rate_limit SET compteur = compteur + 1 WHERE cle = :cle')
            ->execute(['cle' => $key]);

        return true;
    }

    /** Seconds before the current window resets (for the Retry-After header). */
    public function retryAfter(string $key, int $windowSeconds): int
    {
        $stmt = $this->db->prepare('SELECT fenetre_debut FROM rate_limit WHERE cle = :cle');
        $stmt->execute(['cle' => $key]);
        $start = $stmt->fetchColumn();

        if ($start === false) {
            return 0;
        }

        return max(0, $windowSeconds - (time() - (int) $start));
    }
}
