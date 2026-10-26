<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Local memberships: one role per (space, user), dated.
 *
 * Ending a membership writes a row into `historique_adhesions`; the row is
 * never physically deleted.
 */
final class Adhesion
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $espaceId, int $utilisateurId, string $roleLocal = 'lecteur'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO adhesion (espace_id, utilisateur_id, role_local) VALUES (:espace, :utilisateur, :role)'
        );
        $stmt->execute([
            'espace' => $espaceId,
            'utilisateur' => $utilisateurId,
            'role' => $roleLocal,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(int $espaceId, int $utilisateurId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, espace_id, utilisateur_id, role_local, debut_le, fin_le
             FROM adhesion
             WHERE espace_id = :espace AND utilisateur_id = :utilisateur
               AND debut_le <= CURRENT_DATE AND (fin_le IS NULL OR fin_le >= CURRENT_DATE)
             LIMIT 1'
        );
        $stmt->execute(['espace' => $espaceId, 'utilisateur' => $utilisateurId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function end(int $adhesionId): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, role_local, debut_le FROM adhesion WHERE id = :id AND fin_le IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $adhesionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return;
        }

        $this->db->prepare(
            'INSERT INTO historique_adhesions (adhesion_id, role_local, debut_le, fin_le)
             VALUES (:adhesion, :role, :debut, CURRENT_DATE)'
        )->execute([
            'adhesion' => $adhesionId,
            'role' => (string) $row['role_local'],
            'debut' => (string) $row['debut_le'],
        ]);

        $this->db->prepare('UPDATE adhesion SET fin_le = CURRENT_DATE WHERE id = :id')
            ->execute(['id' => $adhesionId]);
    }
}
