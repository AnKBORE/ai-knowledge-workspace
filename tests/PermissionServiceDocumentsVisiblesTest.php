<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\PermissionService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * The visibility rule behind every read endpoint and the retrieval engine: a
 * document is visible through the union of its workspaces, the owner always
 * keeps access, and a private document never leaks.
 */
final class PermissionServiceDocumentsVisiblesTest extends TestCase
{
    private PDO $pdo;
    private PermissionService $permissions;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('CREATE TABLE utilisateur (
            id INTEGER PRIMARY KEY,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            role_global TEXT NOT NULL DEFAULT "membre",
            statut TEXT NOT NULL DEFAULT "actif",
            supprime_le TEXT NULL
        )');

        $this->pdo->exec('CREATE TABLE espace (
            id INTEGER PRIMARY KEY,
            parent_id INTEGER NULL,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            statut TEXT NOT NULL DEFAULT "actif",
            visibilite TEXT NOT NULL DEFAULT "restreint",
            supprime_le TEXT NULL
        )');

        $this->pdo->exec('CREATE TABLE adhesion (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            espace_id INTEGER NOT NULL,
            utilisateur_id INTEGER NOT NULL,
            role_local TEXT NOT NULL DEFAULT "lecteur",
            debut_le DATE NOT NULL DEFAULT (CURRENT_DATE),
            fin_le DATE NULL
        )');

        $this->pdo->exec('CREATE TABLE documents (
            id INTEGER PRIMARY KEY,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            espace_id INTEGER NULL,
            confidentialite TEXT NOT NULL DEFAULT "restreint",
            proprietaire_id INTEGER NULL,
            supprime_le DATETIME NULL
        )');

        $this->pdo->exec('CREATE TABLE document_espace (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            espace_id INTEGER NOT NULL,
            UNIQUE (document_id, espace_id)
        )');

        $this->pdo->exec("INSERT INTO utilisateur (id, role_global) VALUES (1, 'administrateur_technique')");
        $this->pdo->exec("INSERT INTO utilisateur (id, role_global) VALUES (2, 'membre')");
        $this->pdo->exec("INSERT INTO utilisateur (id, role_global) VALUES (3, 'membre')");
        $this->pdo->exec("INSERT INTO utilisateur (id, role_global) VALUES (4, 'membre')");

        $this->pdo->exec("INSERT INTO espace (id, visibilite) VALUES (10, 'restreint')");
        $this->pdo->exec("INSERT INTO espace (id, visibilite) VALUES (20, 'restreint')");
        $this->pdo->exec("INSERT INTO espace (id, visibilite) VALUES (30, 'public_interne')");

        $this->pdo->exec("INSERT INTO adhesion (espace_id, utilisateur_id, role_local) VALUES (10, 2, 'lecteur')");
        $this->pdo->exec("INSERT INTO adhesion (espace_id, utilisateur_id, role_local) VALUES (20, 3, 'lecteur')");

        // 100: space 10 only.  101: space 20 only.  102: both.  103: private.
        // 104: internal public space.  105: no workspace, owned by user 4.
        $this->pdo->exec("INSERT INTO documents (id, espace_id, confidentialite, proprietaire_id) VALUES (100, 10, 'restreint', 1)");
        $this->pdo->exec("INSERT INTO documents (id, espace_id, confidentialite, proprietaire_id) VALUES (101, 20, 'restreint', 1)");
        $this->pdo->exec("INSERT INTO documents (id, espace_id, confidentialite, proprietaire_id) VALUES (102, 10, 'restreint', 1)");
        $this->pdo->exec("INSERT INTO documents (id, espace_id, confidentialite, proprietaire_id) VALUES (103, 10, 'prive', 1)");
        $this->pdo->exec("INSERT INTO documents (id, espace_id, confidentialite, proprietaire_id) VALUES (104, 30, 'restreint', 1)");
        $this->pdo->exec("INSERT INTO documents (id, espace_id, confidentialite, proprietaire_id) VALUES (105, NULL, 'restreint', 4)");

        $this->pdo->exec('INSERT INTO document_espace (document_id, espace_id) VALUES (100, 10)');
        $this->pdo->exec('INSERT INTO document_espace (document_id, espace_id) VALUES (101, 20)');
        $this->pdo->exec('INSERT INTO document_espace (document_id, espace_id) VALUES (102, 10)');
        $this->pdo->exec('INSERT INTO document_espace (document_id, espace_id) VALUES (102, 20)');
        $this->pdo->exec('INSERT INTO document_espace (document_id, espace_id) VALUES (103, 10)');
        $this->pdo->exec('INSERT INTO document_espace (document_id, espace_id) VALUES (104, 30)');

        $this->permissions = new PermissionService($this->pdo);
    }

    /** @return list<int> */
    private function ids(int $userId): array
    {
        $visible = $this->permissions->documentsVisibles($userId);

        if ($visible === true) {
            return [100, 101, 102, 103, 104, 105];
        }

        $ids = array_keys($visible);
        sort($ids);

        return $ids;
    }

    public function testAdministratorSeesEveryDocumentOfTheTenant(): void
    {
        self::assertSame([100, 101, 102, 103, 104, 105], $this->ids(1));
    }

    public function testMemberOnlySeesTheDocumentsOfTheirOwnSpace(): void
    {
        // 104 lives in an internal public space: any active member sees it.
        self::assertSame([100, 102, 104], $this->ids(2));
        self::assertSame([101, 102, 104], $this->ids(3));
    }

    public function testDocumentFiledInTwoSpacesIsVisibleThroughEitherOne(): void
    {
        // The union rule: one accessible workspace is enough.
        self::assertContains(102, $this->ids(2));
        self::assertContains(102, $this->ids(3));
    }

    public function testPrivateDocumentNeverLeaksToTheSpace(): void
    {
        self::assertNotContains(103, $this->ids(2));
        self::assertNotContains(103, $this->ids(3));
    }

    public function testInternalPublicSpaceIsVisibleToAnyActiveMember(): void
    {
        self::assertContains(104, $this->ids(4));
    }

    public function testOwnerKeepsAccessToADocumentWithoutWorkspace(): void
    {
        self::assertContains(105, $this->ids(4));
        self::assertNotContains(105, $this->ids(2));
    }

    public function testAnInactiveAccountSeesNothing(): void
    {
        $this->pdo->exec("UPDATE utilisateur SET statut = 'suspendu' WHERE id = 2");

        self::assertSame([], $this->permissions->documentsVisibles(2));
    }

    /*
     * The single-document question every endpoint taking a document id must ask.
     *
     * These cases lock the rule the audit enforced: holding `document.consulter`
     * somewhere never means "may read this document", and an id that the user
     * cannot see must answer false rather than fall through.
     */
    public function testCanConsultDocumentFollowsTheSameRuleAsTheList(): void
    {
        self::assertTrue($this->permissions->peutConsulterDocument(2, 100));
        self::assertTrue($this->permissions->peutConsulterDocument(2, 102));
        self::assertFalse($this->permissions->peutConsulterDocument(2, 101), 'Another workspace must stay closed.');
        self::assertFalse($this->permissions->peutConsulterDocument(2, 103), 'A private document never leaks.');
        self::assertFalse($this->permissions->peutConsulterDocument(2, 105), 'An owner outside the workspace is closed.');
    }

    public function testCanConsultDocumentRefusesUnknownAndEmptyIds(): void
    {
        self::assertFalse($this->permissions->peutConsulterDocument(2, 0));
        self::assertFalse($this->permissions->peutConsulterDocument(2, -3));
        self::assertFalse($this->permissions->peutConsulterDocument(2, 9999));
    }

    public function testAdministratorCanConsultAnyListedDocument(): void
    {
        foreach ([100, 101, 102, 103, 104, 105] as $documentId) {
            self::assertTrue($this->permissions->peutConsulterDocument(1, $documentId));
        }
    }}
