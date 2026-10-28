<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\Ai\EmbeddingService;
use App\Services\Ai\RagService;
use App\Services\PermissionService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * RBAC scoping of the RAG retrieval: a user must never receive chunks (or a
 * summary) from documents they are not allowed to consult.
 */
final class RagServiceScopeTest extends TestCase
{
    private PDO $pdo;
    private RagService $rag;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE utilisateur (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            role_global TEXT NOT NULL DEFAULT \'membre\',
            statut TEXT NOT NULL DEFAULT \'actif\',
            supprime_le DATETIME NULL
        )');
        $this->pdo->exec('CREATE TABLE espace (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            parent_id INTEGER NULL,
            nom TEXT NOT NULL,
            visibilite TEXT NOT NULL DEFAULT \'restreint\',
            statut TEXT NOT NULL DEFAULT \'actif\',
            supprime_le DATETIME NULL
        )');
        $this->pdo->exec('CREATE TABLE adhesion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            espace_id INTEGER NOT NULL,
            utilisateur_id INTEGER NOT NULL,
            role_local TEXT NOT NULL DEFAULT \'lecteur\',
            debut_le DATE NOT NULL DEFAULT (CURRENT_DATE),
            fin_le DATE NULL
        )');
        $this->pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            espace_id INTEGER NULL,
            title TEXT NOT NULL,
            confidentialite TEXT NOT NULL DEFAULT \'restreint\',
            proprietaire_id INTEGER NULL,
            supprime_le DATETIME NULL
        )');
        $this->pdo->exec('CREATE TABLE document_chunks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            version_document_id INTEGER NULL,
            chunk_index INTEGER NOT NULL,
            content TEXT NOT NULL,
            embedding TEXT NOT NULL
        )');

        $client = new FakeMetaClient();
        $this->rag = new RagService($client, new EmbeddingService($client), $this->pdo, new PermissionService($this->pdo));
    }

    private function insertUser(string $roleGlobal = 'membre'): int
    {
        $this->pdo->exec('INSERT INTO utilisateur (role_global) VALUES (' . $this->pdo->quote($roleGlobal) . ')');

        return (int) $this->pdo->lastInsertId();
    }

    private function insertSpace(string $visibilite = 'restreint'): int
    {
        $this->pdo->exec('INSERT INTO espace (nom, visibilite) VALUES (\'S\', ' . $this->pdo->quote($visibilite) . ')');

        return (int) $this->pdo->lastInsertId();
    }

    private function insertAdhesion(int $userId, int $spaceId, string $role = 'lecteur'): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO adhesion (espace_id, utilisateur_id, role_local) VALUES (?, ?, ?)');
        $stmt->execute([$spaceId, $userId, $role]);
    }

    private function insertDocument(string $title, ?int $spaceId, string $confidentialite = 'restreint', ?int $ownerId = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO documents (title, espace_id, confidentialite, proprietaire_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$title, $spaceId, $confidentialite, $ownerId]);
        $id = (int) $this->pdo->lastInsertId();

        $this->rag->indexDocument($id, 'Le rapport unique ' . $title . ' decrit les risques majeurs du projet.');

        return $id;
    }

    /** @return list<string> */
    private function sourceTitles(array $result): array
    {
        return array_map(static fn (array $s): string => $s['title'], $result['sources'] ?? []);
    }

    public function testMemberSeesOnlyDocumentsOfTheirSpaces(): void
    {
        $user = $this->insertUser();
        $spaceA = $this->insertSpace();
        $spaceB = $this->insertSpace();
        $this->insertAdhesion($user, $spaceA);

        $this->insertDocument('Rapport A', $spaceA);
        $this->insertDocument('Rapport B', $spaceB);

        $titles = $this->sourceTitles($this->rag->answer('risques', 6, $user));

        $this->assertContains('Rapport A', $titles);
        $this->assertNotContains('Rapport B', $titles);
    }

    public function testPrivateConfidentialityIsOwnerOnly(): void
    {
        $owner = $this->insertUser();
        $member = $this->insertUser();
        $space = $this->insertSpace();
        $this->insertAdhesion($owner, $space, 'responsable');
        $this->insertAdhesion($member, $space, 'lecteur');

        $this->insertDocument('Note privee', $space, 'prive', $owner);

        $this->assertContains('Note privee', $this->sourceTitles($this->rag->answer('risques', 6, $owner)));
        $this->assertNotContains('Note privee', $this->sourceTitles($this->rag->answer('risques', 6, $member)));
    }

    public function testPublicInternalSpaceIsVisibleToMembersWithoutAdhesion(): void
    {
        $user = $this->insertUser();
        $space = $this->insertSpace('public_interne');
        $owner = $this->insertUser();

        $this->insertDocument('Guide interne', $space, 'restreint', $owner);

        $this->assertContains('Guide interne', $this->sourceTitles($this->rag->answer('risques', 6, $user)));
    }

    public function testGlobalAdministratorSeesEverythingInTenant(): void
    {
        $admin = $this->insertUser('administrateur_fonctionnel');
        $other = $this->insertUser();
        $space = $this->insertSpace('prive');

        $this->insertDocument('Document restreint', $space, 'restreint', $other);

        $this->assertContains('Document restreint', $this->sourceTitles($this->rag->answer('risques', 6, $admin)));
    }

    public function testUnknownUserGetsNoSources(): void
    {
        $space = $this->insertSpace();
        $this->insertDocument('Rapport', $space);

        $result = $this->rag->answer('risques', 6, 999);

        $this->assertSame([], $result['sources']);
        $this->assertStringContainsString('provided documents', $result['answer']);
    }

    public function testSoftDeletedDocumentsAreExcluded(): void
    {
        $admin = $this->insertUser('administrateur_technique');
        $docId = $this->insertDocument('Archive supprimee', null, 'restreint', $admin);
        $this->pdo->exec('UPDATE documents SET supprime_le = CURRENT_TIMESTAMP WHERE id = ' . $docId);

        $result = $this->rag->answer('risques', 6, $admin);

        $this->assertNotContains('Archive supprimee', $this->sourceTitles($result));
    }

    public function testSummarizeRefusesInaccessibleDocument(): void
    {
        $owner = $this->insertUser();
        $member = $this->insertUser();
        $docId = $this->insertDocument('Note privee', null, 'prive', $owner);

        $result = $this->rag->summarize($docId, 20, $member);

        $this->assertSame([], $result['sources']);
        $this->assertStringContainsString('not accessible', $result['summary']);
    }
}
