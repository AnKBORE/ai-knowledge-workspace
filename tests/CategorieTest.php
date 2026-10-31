<?php

declare(strict_types=1);

namespace App\Tests;

use App\Models\Categorie;
use PDO;
use PHPUnit\Framework\TestCase;

final class CategorieTest extends TestCase
{
    private PDO $pdo;
    private Categorie $categories;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE categorie (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom TEXT NOT NULL,
            slug TEXT NOT NULL,
            description TEXT NULL,
            couleur TEXT NOT NULL DEFAULT "#4a63c8",
            cree_le TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        $this->pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            categorie_id INTEGER NULL,
            supprime_le TEXT NULL
        )');

        $this->categories = new Categorie($this->pdo);
    }

    public function testCreateGeneratesAnAutomaticIdAndASlug(): void
    {
        $first = $this->categories->create("Rapports d'activité", null, '#17752d');
        $second = $this->categories->create('Procédures', null, '#4a63c8');

        $this->assertGreaterThan(0, $first);
        $this->assertGreaterThan($first, $second);

        $row = $this->categories->find($first);
        $this->assertSame('Rapports d\'activité', $row['nom']);
        $this->assertSame('rapports-d-activite', $row['slug']);
        $this->assertSame('#17752d', $row['couleur']);
    }

    public function testListAllCountsLinkedDocuments(): void
    {
        $id = $this->categories->create('Rapports', null, '#17752d');
        $this->pdo->exec('INSERT INTO documents (categorie_id) VALUES (' . $id . ')');
        $this->pdo->exec('INSERT INTO documents (categorie_id, supprime_le) VALUES (' . $id . ", CURRENT_TIMESTAMP)");

        $list = $this->categories->listAll();

        $this->assertCount(1, $list);
        $this->assertSame(1, (int) $list[0]['documents']);
    }

    public function testUpdateChangesNameAndSlug(): void
    {
        $id = $this->categories->create('Ancien', null, '#000000');
        $this->categories->update($id, 'Nouveau nom', 'Description', '#ffffff');

        $row = $this->categories->find($id);

        $this->assertSame('Nouveau nom', $row['nom']);
        $this->assertSame('nouveau-nom', $row['slug']);
        $this->assertSame('Description', $row['description']);
    }

    public function testDeleteRemovesTheCategory(): void
    {
        $id = $this->categories->create('Temporaire', null, '#000000');
        $this->categories->delete($id);

        $this->assertNull($this->categories->find($id));
        $this->assertSame(0, $this->categories->count());
    }

    public function testSlugifyHandlesAccentsAndSpaces(): void
    {
        $this->assertSame('formation-continue', $this->categories->slugify('Formation continue'));
        $this->assertSame('procedures', $this->categories->slugify('Procédures'));
    }
}
