<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\JournalService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Append-only audit journal (SQLite in memory).
 */
final class JournalServiceTest extends TestCase
{
    private PDO $pdo;
    private JournalService $journal;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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
        $this->journal = new JournalService($this->pdo);
    }

    public function testLogsAnAction(): void
    {
        $this->journal->log(1, 'document.depot', 'document', 42, 'succes', ['version' => 1], '127.0.0.1');

        $row = (array) $this->pdo->query('SELECT * FROM journal')->fetch();

        $this->assertSame('document.depot', $row['action']);
        $this->assertSame('document', $row['objet_type']);
        $this->assertSame(42, (int) $row['objet_id']);
        $this->assertSame('succes', $row['resultat']);
        $this->assertSame('127.0.0.1', $row['adresse_ip']);
        $this->assertSame(['version' => 1], json_decode((string) $row['details'], true));
    }

    public function testInvalidIpFallsBackToZero(): void
    {
        $this->journal->log(1, 'test', null, null, 'succes', [], 'not-an-ip');

        $row = (array) $this->pdo->query('SELECT adresse_ip FROM journal')->fetch();

        $this->assertSame('0.0.0.0', $row['adresse_ip']);
    }

    public function testAuditFailureDoesNotThrow(): void
    {
        $this->pdo->exec('DROP TABLE journal');

        $this->journal->log(1, 'test');

        $this->addToAssertionCount(1);
    }
}
