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
        bool $compteSuper = false,
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO utilisateur (courriel, nom, prenom, mot_de_passe, role_global, compte_super)
             VALUES (:courriel, :nom, :prenom, :mot_de_passe, :role_global, :compte_super)'
        );
        $stmt->execute([
            'courriel' => strtolower(trim($courriel)),
            'nom' => $nom,
            'prenom' => $prenom,
            'mot_de_passe' => $motDePasseHash,
            'role_global' => $roleGlobal,
            'compte_super' => $compteSuper ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * The Super Admin is the protected installation account: it can never be
     * deleted, suspended or demoted (its profile stays editable by its owner).
     */
    /**
     * The oldest protected account: the anchor used by the bootstrap script
     * after a wipe. Several accounts may be protected at the same time.
     */
    public function compteSuperId(): ?int
    {
        $row = $this->db->query(
            'SELECT id FROM utilisateur
             WHERE compte_super = 1 AND supprime_le IS NULL
             ORDER BY id
             LIMIT 1'
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : (int) $row['id'];
    }

    /**
     * Protection is a per-account property now: any number of accounts may hold
     * it, but at least one must always remain.
     */
    public function estCompteSuper(int $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM utilisateur
             WHERE id = :id AND compte_super = 1 AND supprime_le IS NULL'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Every protected account, oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function superAdmins(): array
    {
        $stmt = $this->db->query(
            'SELECT id, courriel, nom, prenom, role_global, statut
             FROM utilisateur
             WHERE compte_super = 1 AND supprime_le IS NULL
             ORDER BY id'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * The Super Admin is the bootstrap account: exactly one, always an active
     * technical administrator, never deletable, never suspended, never demoted.
     * The invariants live here so every caller inherits them.
     */
    /**
     * Adds an account to the set of protected accounts. Idempotent: granting
     * twice changes nothing, and other protected accounts keep the flag.
     */
    public function accorderSuperAdmin(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE utilisateur
             SET compte_super = 1, role_global = :role, statut = :statut, supprime_le = NULL
             WHERE id = :id'
        );
        $stmt->execute(['role' => 'administrateur_technique', 'statut' => 'actif', 'id' => $id]);
    }

    /**
     * Removes the protection from one account. The installation must never be
     * left without a protected account, so the last one cannot be removed.
     *
     * @throws \RuntimeException when the target is the last protected account
     */
    public function retirerSuperAdmin(int $id): void
    {
        if (!$this->estCompteSuper($id)) {
            return;
        }

        $stmt = $this->db->query('SELECT COUNT(*) FROM utilisateur WHERE compte_super = 1 AND supprime_le IS NULL');
        $proteges = (int) $stmt->fetchColumn();

        if ($proteges <= 1) {
            throw new \RuntimeException('dernier_super_admin');
        }

        $this->db->prepare('UPDATE utilisateur SET compte_super = 0 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /** @throws \RuntimeException when the target is the protected account */
    public function changerRole(int $id, string $role): void
    {
        if (!in_array($role, ['membre', 'administrateur_fonctionnel', 'administrateur_technique'], true)) {
            throw new \InvalidArgumentException('Unknown global role.');
        }

        if ($this->estCompteSuper($id) && $role !== 'administrateur_technique') {
            throw new \RuntimeException('super_admin_protege');
        }

        $stmt = $this->db->prepare('UPDATE utilisateur SET role_global = :role WHERE id = :id AND supprime_le IS NULL');
        $stmt->execute(['role' => $role, 'id' => $id]);
    }

    /** @throws \RuntimeException when the target is the protected account */
    public function changerStatut(int $id, string $statut): void
    {
        if (!in_array($statut, ['actif', 'suspendu'], true)) {
            throw new \InvalidArgumentException('Unknown status.');
        }

        if ($this->estCompteSuper($id) && $statut !== 'actif') {
            throw new \RuntimeException('super_admin_protege');
        }

        $stmt = $this->db->prepare('UPDATE utilisateur SET statut = :statut WHERE id = :id AND supprime_le IS NULL');
        $stmt->execute(['statut' => $statut, 'id' => $id]);
    }

    /** @throws \RuntimeException when the target is the protected account */
    public function supprimerLogique(int $id): void
    {
        if ($this->estCompteSuper($id)) {
            throw new \RuntimeException('super_admin_protege');
        }

        $stmt = $this->db->prepare('UPDATE utilisateur SET supprime_le = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
    /**
     * Password hash of an account (for sensitive-action verification).
     */

    /**
     * Every account, for the administration page.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, courriel, nom, prenom, role_global, statut, compte_super, cree_le
             FROM utilisateur
             WHERE supprime_le IS NULL
             ORDER BY id'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
