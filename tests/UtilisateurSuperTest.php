<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Utilisateur;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Protected (Super Admin) accounts: several may coexist, they are protected
 * against deletion, suspension and demotion, and the installation must never
 * be left without one.
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

    public function test_several_protected_accounts_can_coexist(): void
    {
        $first = $this->users->create('a@org.org', 'Un', 'Admin', 'hash', 'administrateur_technique', true);
        $second = $this->users->create('b@org.org', 'Deux', 'Admin', 'hash', 'membre');

        $this->users->accorderSuperAdmin($second);

        $this->assertTrue($this->users->estCompteSuper($first));
        $this->assertTrue($this->users->estCompteSuper($second));
        $this->assertCount(2, $this->users->superAdmins());
    }

    public function test_compte_super_id_returns_the_oldest(): void
    {
        $first = $this->users->create('a@org.org', 'Un', 'Admin', 'hash', 'administrateur_technique', true);
        $second = $this->users->create('b@org.org', 'Deux', 'Admin', 'hash', 'membre');
        $this->users->accorderSuperAdmin($second);

        $this->assertSame($first, $this->users->compteSuperId());
    }

    public function test_no_protected_account_returns_null(): void
    {
        $this->users->create('plain@org.org', 'Demo', 'Membre', 'hash', 'membre');

        $this->assertNull($this->users->compteSuperId());
        $this->assertSame([], $this->users->superAdmins());
    }

    public function test_protected_account_cannot_be_demoted_suspended_or_deleted(): void
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

    public function test_revoking_one_protected_account_keeps_the_others(): void
    {
        $first = $this->users->create('a@org.org', 'Un', 'Admin', 'hash', 'administrateur_technique', true);
        $second = $this->users->create('b@org.org', 'Deux', 'Admin', 'hash', 'administrateur_technique', true);

        $this->users->retirerSuperAdmin($second);

        $this->assertTrue($this->users->estCompteSuper($first));
        $this->assertFalse($this->users->estCompteSuper($second));
        $this->assertCount(1, $this->users->superAdmins());
    }

    public function test_the_last_protected_account_cannot_be_revoked(): void
    {
        $only = $this->users->create('only@org.org', 'Seul', 'Admin', 'hash', 'administrateur_technique', true);

        try {
            $this->users->retirerSuperAdmin($only);
            $this->fail('The installation was left without a protected account.');
        } catch (RuntimeException $e) {
            $this->assertSame('dernier_super_admin', $e->getMessage());
        }

        $this->assertTrue($this->users->estCompteSuper($only));
    }

    public function test_granting_restores_an_account_and_makes_it_technical(): void
    {
        $account = $this->users->create('late@org.org', 'Tardif', 'Admin', 'hash', 'membre');
        $this->pdo->exec("UPDATE utilisateur SET statut = 'suspendu', supprime_le = CURRENT_TIMESTAMP WHERE id = {$account}");

        $this->users->accorderSuperAdmin($account);

        $row = $this->users->findById($account);
        $this->assertNotNull($row);
        $this->assertSame('administrateur_technique', $row['role_global']);
        $this->assertSame('actif', $row['statut']);
        $this->assertTrue($this->users->estCompteSuper($account));
    }

    public function test_soft_deleted_protected_account_is_no_longer_listed(): void
    {
        $superId = $this->users->create('super@org.org', 'Super', 'Admin', 'hash', 'administrateur_technique', true);
        $this->pdo->prepare('UPDATE utilisateur SET supprime_le = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$superId]);

        $this->assertNull($this->users->compteSuperId());
        $this->assertSame([], $this->users->superAdmins());
        $this->assertCount(0, $this->users->all());
    }
}
