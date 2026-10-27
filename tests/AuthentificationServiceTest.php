<?php

declare(strict_types=1);

namespace App\Tests;

use App\Core\Auth;
use App\Core\Session;
use App\Models\TentativeConnexion;
use App\Models\Utilisateur;
use App\Services\AuthentificationService;
use App\Services\JournalService;
use App\Validators\PolitiqueMotDePasse;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for authentication (SQLite in memory, no database server).
 */
final class AuthentificationServiceTest extends TestCase
{
    private PDO $pdo;
    private Auth $auth;
    private AuthentificationService $service;

    protected function setUp(): void
    {
        $_SESSION = [];

        $pdo = new PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE utilisateur (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL DEFAULT 1,
            courriel TEXT NOT NULL UNIQUE,
            nom TEXT NOT NULL,
            prenom TEXT NOT NULL,
            mot_de_passe TEXT NOT NULL,
            role_global TEXT NOT NULL DEFAULT "membre",
            statut TEXT NOT NULL DEFAULT "actif",
            derniere_connexion_le TEXT NULL,
            cree_le TEXT DEFAULT CURRENT_TIMESTAMP,
            maj_le TEXT DEFAULT CURRENT_TIMESTAMP,
            categorie_id INTEGER NULL,
            supprime_le TEXT NULL
        )');
        $pdo->exec('CREATE TABLE tentative_connexion (
            empreinte_courriel TEXT PRIMARY KEY,
            echecs INTEGER NOT NULL DEFAULT 0,
            verrouille_jusqua TEXT NULL,
            maj_le TEXT DEFAULT CURRENT_TIMESTAMP
        )');
        $pdo->exec('CREATE TABLE journal (
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
        $this->pdo = $pdo;

        $users = new Utilisateur($pdo);
        $users->create(
            'admin@example.com',
            'KABORE',
            'Anatole',
            PolitiqueMotDePasse::hash('Hackathon2026!'),
            'administrateur_technique',
        );

        $this->auth = new Auth(new Session());
        $this->service = new AuthentificationService(
            $users,
            new TentativeConnexion($pdo),
            $this->auth,
            new JournalService($pdo),
        );
    }

    public function testLoginSucceedsWithValidCredentials(): void
    {
        $this->assertTrue($this->service->login('admin@example.com', 'Hackathon2026!'));
        $this->assertTrue($this->auth->check());

        $user = $this->auth->user();
        $this->assertSame('admin@example.com', $user['courriel']);
        $this->assertArrayNotHasKey('mot_de_passe', $user);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->assertFalse($this->service->login('admin@example.com', 'wrong'));
        $this->assertFalse($this->auth->check());
    }

    public function testLoginLocksAfterTooManyFailures(): void
    {
        for ($i = 0; $i < TentativeConnexion::MAX_ECHECS; $i++) {
            $this->assertFalse($this->service->login('admin@example.com', 'wrong'));
        }

        $this->assertFalse($this->service->login('admin@example.com', 'Hackathon2026!'));
        $this->assertFalse($this->auth->check());
    }

    public function testLoginIsJournalised(): void
    {
        $this->service->login('admin@example.com', 'Hackathon2026!');

        $count = (int) $this->pdo
            ->query("SELECT COUNT(*) FROM journal WHERE action = 'connexion' AND resultat = 'succes'")
            ->fetchColumn();

        $this->assertSame(1, $count);
    }

    public function testLogoutClearsSession(): void
    {
        $this->assertTrue($this->service->login('admin@example.com', 'Hackathon2026!'));
        $this->service->logout();

        $this->assertFalse($this->auth->check());
    }
}
