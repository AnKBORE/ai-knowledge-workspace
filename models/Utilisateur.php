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

    /**
     * Password hash of an account (for sensitive-action verification).
     */
    public function motDePasseHash(int $id): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT mot_de_passe FROM utilisateur
             WHERE id = :id AND supprime_le IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $hash = $stmt->fetch(PDO::FETCH_COLUMN);

        return $hash === false ? null : (string) $hash;
    }

    /**
     * Whether an email is already used by another active account.
     */
    public function courrielPris(string $courriel, int $excludeId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM utilisateur
             WHERE courriel = :courriel AND id <> :id AND supprime_le IS NULL'
        );
        $stmt->execute(['courriel' => strtolower(trim($courriel)), 'id' => $excludeId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Update identity fields of an account.
     */
    public function updateProfil(int $id, string $prenom, string $nom, string $courriel): void
    {
        $this->db->prepare(
            'UPDATE utilisateur
             SET prenom = :prenom, nom = :nom, courriel = :courriel
             WHERE id = :id'
        )->execute([
            'prenom' => $prenom,
            'nom' => $nom,
            'courriel' => strtolower(trim($courriel)),
            'id' => $id,
        ]);
    }

    /**
     * Update the password hash of an account.
     */
    public function updateMotDePasse(int $id, string $hash): void
    {
        $this->db->prepare('UPDATE utilisateur SET mot_de_passe = :hash WHERE id = :id')
            ->execute(['hash' => $hash, 'id' => $id]);
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->prepare('UPDATE utilisateur SET derniere_connexion_le = CURRENT_TIMESTAMP WHERE id = :id')
            ->execute(['id' => $id]);
    }
}
