<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * User accounts (global, one per installation).
 *
 * Deletion is logical: a deleted account keeps its row with `supprime_le`.
 */
final class Utilisateur
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $courriel): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tenant_id, courriel, nom, prenom, mot_de_passe, role_global, statut
             FROM utilisateur
             WHERE courriel = :courriel AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['courriel' => strtolower(trim($courriel))]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, tenant_id, courriel, nom, prenom, role_global, statut
             FROM utilisateur
             WHERE id = :id AND supprime_le IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Create an account. The password must already be hashed.
     */
    public function create(
        string $courriel,
        string $nom,
        string $prenom,
        string $motDePasseHash,
        string $roleGlobal = 'membre',
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO utilisateur (courriel, nom, prenom, mot_de_passe, role_global)
             VALUES (:courriel, :nom, :prenom, :mot_de_passe, :role_global)'
        );
        $stmt->execute([
            'courriel' => strtolower(trim($courriel)),
            'nom' => $nom,
            'prenom' => $prenom,
            'mot_de_passe' => $motDePasseHash,
            'role_global' => $roleGlobal,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->prepare('UPDATE utilisateur SET derniere_connexion_le = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
