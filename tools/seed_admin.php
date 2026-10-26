<?php

declare(strict_types=1);

/**
 * Create the first administrator account (idempotent).
 *
 * Usage:
 *   php tools/seed_admin.php
 *
 * Override with environment variables: ADMIN_EMAIL, ADMIN_PASSWORD,
 * ADMIN_NOM, ADMIN_PRENOM.
 */

require dirname(__DIR__) . '/core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/vendor/autoload.php';

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
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);

$courriel = getenv('ADMIN_EMAIL') ?: 'anatolekbore@gmail.com';
$motDePasse = getenv('ADMIN_PASSWORD') ?: 'Hackathon2026!';
$nom = getenv('ADMIN_NOM') ?: 'KABORE';
$prenom = getenv('ADMIN_PRENOM') ?: 'Anatole';

$users = new Utilisateur($pdo);

if ($users->findByEmail($courriel) !== null) {
    echo "Admin already exists: {$courriel}\n";
    exit(0);
}

$errors = PolitiqueMotDePasse::validate($motDePasse);
if ($errors !== []) {
    fwrite(STDERR, "Password rejected:\n" . implode("\n", $errors) . "\n");
    exit(1);
}

$users->create($courriel, $nom, $prenom, PolitiqueMotDePasse::hash($motDePasse), 'administrateur_technique');

echo "Admin created: {$courriel}\n";
