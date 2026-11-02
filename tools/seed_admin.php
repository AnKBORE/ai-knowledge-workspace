<?php

declare(strict_types=1);

/**
 * Bootstrap the installation: create (or restore) the Super Admin and the
 * default workspace.
 *
 * The Super Admin is the default account — the one you can always come back to,
 * even after wiping the database. This script is idempotent and self-healing:
 *
 *   - it designates exactly one Super Admin (never two);
 *   - it restores a softly deleted or suspended Super Admin;
 *   - it recreates the account from scratch when the table is empty;
 *   - `--reset` sets a new password and prints it.
 *
 * Usage:
 *   php tools/seed_admin.php
 *   php tools/seed_admin.php --reset
 *   php tools/seed_admin.php --transfer=someone@organisation.org
 *
 * Override with: ADMIN_EMAIL, ADMIN_PASSWORD, ADMIN_NOM, ADMIN_PRENOM.
 */

require dirname(__DIR__) . '/core/Env.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\Adhesion;
use App\Models\Espace;
use App\Models\Utilisateur;
use App\Services\JournalService;
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
$nom = getenv('ADMIN_NOM') ?: 'KABORE';
$prenom = getenv('ADMIN_PRENOM') ?: 'Anatole';
$reset = in_array('--reset', $argv ?? [], true);

// --transfer=email designates another existing account as the bootstrap
// account: the flag moves there, and only one account keeps it.
$transfer = null;

foreach ($argv ?? [] as $argument) {
    if (str_starts_with((string) $argument, '--transfer=')) {
        $transfer = strtolower(trim(substr((string) $argument, 11)));
    }
}
$motDePasse = getenv('ADMIN_PASSWORD') ?: ($reset ? 'Super-' . bin2hex(random_bytes(6)) . '-2026!' : 'Hackathon2026!');

// --- 0bis. Help: the three modes and the single-protected-account rule ---
if (in_array('--help', $argv ?? [], true) || in_array('-h', $argv ?? [], true)) {
    $nl = PHP_EOL;
    echo 'Bootstrap account (Super Admin)' . $nl . $nl;
    echo 'Exactly ONE account is protected at a time: it can never be deleted,' . $nl;
    echo 'suspended or demoted. These commands maintain it.' . $nl . $nl;
    echo '  php tools/seed_admin.php                     confirm or recreate it' . $nl;
    echo '  php tools/seed_admin.php --reset             regenerate its password' . $nl;
    echo '  php tools/seed_admin.php --transfer=email    MOVE the protection to another' . $nl;
    echo '                                              account (the previous holder' . $nl;
    echo '                                              loses it)' . $nl . $nl;
    echo 'A transfer is journaled as compte.super_transfert and visible in /journal.' . $nl;
    exit(0);
}

$users = new Utilisateur($pdo);
$journal = new JournalService($pdo);

// --- 0. Explicit transfer ------------------------------------------------
if ($transfer !== null && $transfer !== '') {
    $stmt = $pdo->prepare('SELECT id, role_global, statut FROM utilisateur WHERE courriel = :courriel LIMIT 1');
    $stmt->execute(['courriel' => $transfer]);
    $candidat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($candidat === false) {
        fwrite(STDERR, "No account uses {$transfer}: create it first, then transfer.\n");
        exit(1);
    }

    $ancienRole = (string) $candidat['role_global'];
    $ancienStatut = (string) $candidat['statut'];

    $ancienSuper = $users->compteSuperId();

    $users->designerSuperAdmin((int) $candidat['id']);

    // A privilege change without a trace is not auditable.
    $journal->log(null, 'compte.super_transfert', 'utilisateur', (int) $candidat['id'], 'succes', [
        'avant' => $ancienSuper,
        'apres' => (int) $candidat['id'],
        'ancien_role' => $ancienRole,
    ]);

    echo "Super Admin transferred to {$transfer} (id {$candidat['id']})\n";
    echo "  role   : {$ancienRole} -> administrateur_technique\n";

    if ($ancienStatut !== 'actif') {
        echo "  statut : {$ancienStatut} -> actif\n";
    }

    echo "The previous holder is no longer protected.\n";

    exit(0);
}

// --- 1. Which account is (or becomes) the Super Admin? ----------------------
$superId = $users->compteSuperId();
$action = '';

if ($superId !== null) {
    $action = 'Super Admin confirmed';
    $target = $users->findById($superId);
    $courriel = (string) ($target['courriel'] ?? $courriel);
} else {
    // No Super Admin: reuse the bootstrap address if it exists at all, even
    // softly deleted, otherwise create it.
    $stmt = $pdo->prepare('SELECT id FROM utilisateur WHERE courriel = :courriel LIMIT 1');
    $stmt->execute(['courriel' => strtolower(trim($courriel))]);
    $existingId = $stmt->fetchColumn();

    if ($existingId !== false) {
        $superId = (int) $existingId;
        $action = 'Super Admin restored';
    } else {
        $errors = PolitiqueMotDePasse::validate($motDePasse);

        if ($errors !== []) {
            fwrite(STDERR, "Password rejected:\n" . implode("\n", $errors) . "\n");
            exit(1);
        }

        $superId = $users->create($courriel, $nom, $prenom, PolitiqueMotDePasse::hash($motDePasse), 'administrateur_technique', true);
        $action = 'Super Admin created';
    }
}

// Always re-assert the invariants: one Super Admin, technical, active, alive.
$superAvant = $users->compteSuperId();
$users->designerSuperAdmin((int) $superId);

if ($superAvant !== (int) $superId) {
    $journal->log(null, 'compte.super_designation', 'utilisateur', (int) $superId, 'succes', [
        'avant' => $superAvant,
        'apres' => (int) $superId,
        'action' => $action,
    ]);
}

// --- 2. Password replacement (recovery path) -------------------------------
$motDePasseApplique = null;

if ($reset) {
    $errors = PolitiqueMotDePasse::validate($motDePasse);

    if ($errors !== []) {
        fwrite(STDERR, "Password rejected:\n" . implode("\n", $errors) . "\n");
        exit(1);
    }

    $users->updateMotDePasse((int) $superId, PolitiqueMotDePasse::hash($motDePasse));
    $motDePasseApplique = $motDePasse;
    $action .= ' + password reset';
}

// --- 3. Default workspace + membership (idempotent) ------------------------
$espaces = new Espace($pdo);
$adhesions = new Adhesion($pdo);

$stmt = $pdo->query("SELECT id FROM espace WHERE nom = 'Général' AND supprime_le IS NULL LIMIT 1");
$spaceId = $stmt->fetchColumn();

if ($spaceId === false) {
    $spaceId = $espaces->create('Général', null, 'restreint');
    echo "Space created: Général (#{$spaceId})\n";
}

$spaceId = (int) $spaceId;

if ($adhesions->find($spaceId, (int) $superId) === null) {
    $adhesions->create($spaceId, (int) $superId, 'responsable');
    echo "Membership created: {$courriel} → Général (responsable)\n";
}

// --- 4. Report -------------------------------------------------------------
echo "\n";
echo "{$action}\n";
echo "  id        : {$superId}\n";
echo "  courriel  : {$courriel}\n";
echo "  role      : administrateur_technique (compte_super = 1)\n";

if ($motDePasseApplique !== null) {
    echo "  password  : {$motDePasseApplique}\n";
    echo "\n  Note this password now: it is not stored anywhere in clear.\n";
}

echo "\nProtected account: it can never be deleted, suspended or demoted.\n";
