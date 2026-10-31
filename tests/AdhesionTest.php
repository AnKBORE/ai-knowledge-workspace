<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Adhesion;
use PDO;
use PHPUnit\Framework\TestCase;

final class AdhesionTest extends TestCase
{
    private PDO $pdo;
    private Adhesion $adhesions;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE espace (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            statut TEXT NOT NULL DEFAULT "actif",
            supprime_le TEXT NULL
        )');

        $this->pdo->exec('CREATE TABLE adhesion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            espace_id INTEGER NOT NULL,
            utilisateur_id INTEGER NOT NULL,
            role_local TEXT NOT NULL DEFAULT "lecteur",
            debut_le TEXT DEFAULT CURRENT_DATE,
            fin_le TEXT NULL
        )');

        $this->pdo->exec('CREATE TABLE historique_adhesions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            adhesion_id INTEGER NOT NULL,
            role_local TEXT NOT NULL,
            debut_le TEXT NOT NULL,
            fin_le TEXT NOT NULL
        )');

        $this->adhesions = new Adhesion($this->pdo);
    }

    public function testListForUserReturnsActiveMembershipsWithSpaceName(): void
    {
        $this->pdo->exec("INSERT INTO espace (nom) VALUES ('Général')");
        $espaceId = (int) $this->pdo->lastInsertId();
        $this->adhesions->create($espaceId, 1, 'contributeur');

        $memberships = $this->adhesions->listForUser(1);

        $this->assertCount(1, $memberships);
        $this->assertSame('Général', $memberships[0]['espace_nom']);
        $this->assertSame('contributeur', $memberships[0]['role_local']);
    }

    public function testListForUserIgnoresOtherUsersAndDeletedSpaces(): void
    {
        $this->pdo->exec("INSERT INTO espace (nom) VALUES ('Général')");
        $espaceId = (int) $this->pdo->lastInsertId();
        $this->adhesions->create($espaceId, 1, 'lecteur');

        $this->assertCount(0, $this->adhesions->listForUser(2));

        $this->pdo->exec("UPDATE espace SET supprime_le = CURRENT_TIMESTAMP WHERE id = " . $espaceId);
        $this->assertCount(0, $this->adhesions->listForUser(1));
    }
}
