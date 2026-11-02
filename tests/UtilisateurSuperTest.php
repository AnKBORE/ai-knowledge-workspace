<?php

declare(strict_types=1);

use App\Models\Utilisateur;
use PHPUnit\Framework\TestCase;

/**
 * The Super Admin is the protected installation account: exactly one at a
 * time, flagged at creation, never deletable (soft-delete guard is the
 * endpoint's job — here we verify the flag mechanics).
 */
final class UtilisateurSuperTest extends TestCase
{
    private PDO $pdo;
    private Utilisateur $users;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE utilisateur (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NULL,
            courriel TEXT NOT NULL,
            nom TEXT NOT NULL,
            prenom TEXT NOT NULL,
            mot_de_passe TEXT NOT NULL,
            role_global TEXT NOT NULL DEFAULT "membre",
            statut TEXT NOT NULL DEFAULT "actif",
            compte_super INTEGER NOT NULL DEFAULT 0,
            supprime_le TEXT NULL,
            cree_le TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $this->users = new Utilisateur($this->pdo);
    }

    public function test_created_super_account_is_flagged_and_unique(): void
    {
        $superId = $this->users->create('super@org.org', 'Super', 'Admin', 'hash', 'administrateur_technique', true);
        $this->users->create('plain@org.org', 'Demo', 'Membre', 'hash', 'membre');

        $this->assertSame($superId, $this->users->compteSuperId());
        $this->assertTrue($this->users->estCompteSuper($superId));
        $this->assertFalse($this->users->estCompteSuper($superId + 1));
    }

    public function test_no_super_account_returns_null(): void
    {
        $this->users->create('plain@org.org', 'Demo', 'Membre', 'hash', 'membre');
        $this->assertNull($this->users->compteSuperId());
    }

    public function test_soft_deleted_super_account_is_no_longer_listed(): void
    {
        $superId = $this->users->create('super@org.org', 'Super', 'Admin', 'hash', 'administrateur_technique', true);
        $this->pdo->prepare('UPDATE utilisateur SET supprime_le = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$superId]);

        $this->assertNull($this->users->compteSuperId());
        $this->assertCount(0, $this->users->all());
    }
public function test_super_admin_cannot_be_demoted_suspended_or_deleted(): void
    {
        $superId = $this->users->create('super@org.org', 'Super', 'Admin', 'hash', 'administrateur_technique', true);

        foreach ([
            fn () => $this->users->changerRole($superId, 'membre'),
            fn () => $this->users->changerStatut($superId, 'suspendu'),
            fn () => $this->users->supprimerLogique($superId),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('The protected account accepted a destructive change.');
            } catch (RuntimeException $e) {
                $this->assertSame('super_admin_protege', $e->getMessage());
            }
        }

        $this->assertTrue($this->users->estCompteSuper($superId));
        $row = $this->users->findById($superId);
        $this->assertSame('administrateur_technique', $row['role_global']);
        $this->assertSame('actif', $row['statut']);
    }

    public function test_designating_a_new_super_admin_keeps_it_unique(): void
    {
        $first = $this->users->create('first@org.org', 'First', 'Admin', 'hash', 'administrateur_technique', true);
        $second = $this->users->create('second@org.org', 'Second', 'Admin', 'hash', 'membre');

        $this->users->designerSuperAdmin($second);

        $this->assertSame($second, $this->users->compteSuperId());
        $this->assertFalse($this->users->estCompteSuper($first));

        // The transfer also restores the target to a usable state.
        $row = $this->users->findById($second);
        $this->assertSame('administrateur_technique', $row['role_global']);
        $this->assertSame('actif', $row['statut']);
    }
}
