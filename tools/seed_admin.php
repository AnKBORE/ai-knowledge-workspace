<?php

declare(strict_types=1);

/**
 * Create the first administrator account and the default space (idempotent).
 *
 * Usage:
 *   php tools/seed_admin.php
 *
 * Override with: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NOM, ADMIN_PRENOM.
 */

require dirname(__DIR__) . '/core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\Adhesion;
use App\Models\Espace;
use App\Models\Utilisateur;
use App\Validators\PolitiqueMotDePasse;

$pdo = new PDO(
    sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_DATABASE') ?: 'ai_knowledge_workspace',
        getenv('DB_CHARSET') ?: 'utf8mb4',
    ),
    getenv('DB_USERNAME') ?: 'root',
    getenv('DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);

$courriel = getenv('ADMIN_EMAIL') ?: 'anatolekbore@gmail.com';
$motDePasse = getenv('ADMIN_PASSWORD') ?: 'Hackathon2026!';
$nom = getenv('ADMIN_NOM') ?: 'KABORE';
$prenom = getenv('ADMIN_PRENOM') ?: 'Anatole';

$users = new Utilisateur($pdo);
$user = $users->findByEmail($courriel);

if ($user === null) {
    $errors = PolitiqueMotDePasse::validate($motDePasse);
    if ($errors !== []) {
        fwrite(STDERR, "Password rejected:\n" . implode("\n", $errors) . "\n");
        exit(1);
    }

    $users->create($courriel, $nom, $prenom, PolitiqueMotDePasse::hash($motDePasse), 'administrateur_technique');
    echo "Admin created: {$courriel}\n";

    $user = $users->findByEmail($courriel);
} else {
    echo "Admin already exists: {$courriel}\n";
}

// Default space + membership (idempotent). The global role alone never grants
// local permissions, so the admin needs a membership to use the assistant.
$espaces = new Espace($pdo);
$adhesions = new Adhesion($pdo);

$stmt = $pdo->query("SELECT id FROM espace WHERE nom = 'Général' AND supprime_le IS NULL LIMIT 1");
$spaceId = $stmt->fetchColumn();

if ($spaceId === false) {
    $spaceId = $espaces->create('Général', null, 'restreint');
    echo "Space created: Général (#{$spaceId})\n";
}

$spaceId = (int) $spaceId;

if ($user !== null && $adhesions->find($spaceId, (int) $user['id']) === null) {
    $adhesions->create($spaceId, (int) $user['id'], 'responsable');
    echo "Membership created: {$courriel} → Général (responsable)\n";
}
