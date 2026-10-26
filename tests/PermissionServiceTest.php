<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\PermissionService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Permission resolution: global roles, local roles, tree inheritance and
 * private spaces (SQLite in memory).
 */
final class PermissionServiceTest extends TestCase
{
    private PDO $pdo;
    private PermissionService $permissions;
    private int $membreId;
    private int $adminId;
    private int $rootId;
    private int $childId;
    private int $privateId;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE utilisateur (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            courriel TEXT NOT NULL UNIQUE,
            nom TEXT NOT NULL,
            prenom TEXT NOT NULL,
            mot_de_passe TEXT NOT NULL,
            role_global TEXT NOT NULL DEFAULT "membre",
            statut TEXT NOT NULL DEFAULT "actif",
            supprime_le TEXT NULL
        )');
        $this->pdo->exec('CREATE TABLE espace (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            parent_id INTEGER NULL,
            nom TEXT NOT NULL,
            visibilite TEXT NOT NULL DEFAULT "restreint",
            statut TEXT NOT NULL DEFAULT "actif",
            supprime_le TEXT NULL
        )');
        $this->pdo->exec('CREATE TABLE adhesion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            espace_id INTEGER NOT NULL,
            utilisateur_id INTEGER NOT NULL,
            role_local TEXT NOT NULL DEFAULT "lecteur",
            debut_le TEXT NOT NULL DEFAULT CURRENT_DATE,
            fin_le TEXT NULL
        )');
        $this->pdo->exec('CREATE TABLE journal (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            acteur_id INTEGER NULL,
            action TEXT NOT NULL,
            objet_type TEXT NULL,
            objet_id INTEGER NULL,
            resultat TEXT NOT NULL DEFAULT "succes",
            adresse_ip TEXT NOT NULL,
            details TEXT NULL,
            horodatage TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->membreId = $this->insertUser('membre@example.com', 'membre');
        $this->adminId = $this->insertUser('admin@example.com', 'administrateur_technique');

        $this->rootId = $this->insertSpace('Racine', null, 'restreint');
        $this->childId = $this->insertSpace('Enfant', $this->rootId, 'restreint');
        $this->privateId = $this->insertSpace('Privé', $this->rootId, 'prive');

        $this->addMembership($this->membreId, $this->rootId, 'lecteur');

        $this->permissions = new PermissionService($this->pdo);
    }

    private function insertUser(string $courriel, string $role): int
    {
        $this->pdo->prepare('INSERT INTO utilisateur (courriel, nom, prenom, mot_de_passe, role_global) VALUES (?, ?, ?, ?, ?)')
            ->execute([$courriel, 'Nom', 'Prénom', 'hash', $role]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertSpace(string $nom, ?int $parentId, string $visibilite): int
    {
        $this->pdo->prepare('INSERT INTO espace (nom, parent_id, visibilite) VALUES (?, ?, ?)')
            ->execute([$nom, $parentId, $visibilite]);

        return (int) $this->pdo->lastInsertId();
    }

    private function addMembership(int $userId, int $spaceId, string $role): void
    {
        $this->pdo->prepare('INSERT INTO adhesion (espace_id, utilisateur_id, role_local) VALUES (?, ?, ?)')
            ->execute([$spaceId, $userId, $role]);
    }

    public function testGlobalRoleGrantsItsPermissions(): void
    {
        $this->assertTrue($this->permissions->peut($this->adminId, 'journal.lire'));
        $this->assertFalse($this->permissions->peut($this->membreId, 'journal.lire'));
    }

    public function testMemberWithoutMembershipHasNoLocalPermission(): void
    {
        $this->assertFalse($this->permissions->peut($this->membreId, 'document.consulter', 999));
    }

    public function testReaderCanConsultButNotDeposit(): void
    {
        $this->assertTrue($this->permissions->peut($this->membreId, 'document.consulter', $this->rootId));
        $this->assertFalse($this->permissions->peut($this->membreId, 'document.deposer', $this->rootId));
    }

    public function testLocalRoleIsInheritedFromParent(): void
    {
        $this->assertTrue($this->permissions->peut($this->membreId, 'document.consulter', $this->childId));
    }

    public function testPrivateSpaceBlocksInheritance(): void
    {
        $this->assertFalse($this->permissions->peut($this->membreId, 'document.consulter', $this->privateId));
    }

    public function testContributorCanDeposit(): void
    {
        $this->addMembership($this->membreId, $this->childId, 'contributeur');

        $this->assertTrue($this->permissions->peut($this->membreId, 'document.deposer', $this->childId));
    }

    public function testRefusalIsJournalised(): void
    {
        $this->permissions->journaliserRefus($this->membreId, 'document.deposer', $this->rootId, '127.0.0.1');

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM journal')->fetchColumn();
        $this->assertSame(1, $count);
    }
}
