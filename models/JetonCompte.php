<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Single-use tokens for account activation and password reset.
 *
 * Only the SHA-256 fingerprint is stored; the raw token is returned once.
 */
final class JetonCompte
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $utilisateurId, string $usage, int $ttlMinutes = 60): string
    {
        $token = bin2hex(random_bytes(32));

        $this->db->prepare(
            'INSERT INTO jeton_compte (utilisateur_id, usage_jeton, empreinte, expire_le)
             VALUES (:utilisateur, :usage, :empreinte, :expire)'
        )->execute([
            'utilisateur' => $utilisateurId,
            'usage' => $usage,
            'empreinte' => hash('sha256', $token),
            'expire' => date('Y-m-d H:i:s', time() + $ttlMinutes * 60),
        ]);

        return $token;
    }

    /**
     * Consume a token: returns the user id when valid, null otherwise.
     */
    public function consume(string $token, string $usage): ?int
    {
        $empreinte = hash('sha256', $token);

        $stmt = $this->db->prepare(
            'SELECT id, utilisateur_id FROM jeton_compte
             WHERE empreinte = :empreinte AND usage_jeton = :usage
               AND utilise_le IS NULL AND expire_le >= CURRENT_TIMESTAMP
             LIMIT 1'
        );
        $stmt->execute(['empreinte' => $empreinte, 'usage' => $usage]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $this->db->prepare('UPDATE jeton_compte SET utilise_le = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => (int) $row['id']]);

        return (int) $row['utilisateur_id'];
    }
}
