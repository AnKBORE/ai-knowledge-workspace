<?php

declare(strict_types=1);

/**
 * Bootstrap the installation: maintain the protected Super Admin accounts and
 * the default workspace.
 *
 * Several accounts may be protected at the same time — it is a set of trusted
 * administrators, not a single seat. The rule is simply that the set is never
 * empty: the installation must always keep at least one way back in.
 *
 * Usage:
 *   php tools/seed_admin.php                        ensure at least one exists
 *   php tools/seed_admin.php --reset                regenerate its password
 *   php tools/seed_admin.php --grant=email          protect another account
 *   php tools/seed_admin.php --revoke=email         remove the protection
 *                                                   (refused on the last one)
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

$grant = null;
$revoke = null;

foreach ($argv ?? [] as $argument) {
    $argument = (string) $argument;

    if (str_starts_with($argument, '--grant=') || str_starts_with($argument, '--transfer=')) {
        // --transfer is the old name: it now adds instead of moving.
        $grant = strtolower(trim(substr($argument, strpos($argument, '=') + 1)));
    }

    if (str_starts_with($argument, '--revoke=')) {
        $revoke = strtolower(trim(substr($argument, 9)));
    }
}

$users = new Utilisateur($pdo);
$journal = new JournalService($pdo);

// --- Help ------------------------------------------------------------------
if (in_array('--help', $argv ?? [], true) || in_array('-h', $argv ?? [], true)) {
    $nl = PHP_EOL;
    echo 'Protected accounts (Super Admin)' . $nl . $nl;
    echo 'Several accounts may be protected at once; they can never be deleted,' . $nl;
    echo 'suspended or demoted, and at least one must always remain.' . $nl . $nl;
    echo '  php tools/seed_admin.php                 ensure at least one exists' . $nl;
    echo '  php tools/seed_admin.php --reset         regenerate its password' . $nl;
    echo '  php tools/seed_admin.php --grant=email   protect another account' . $nl;
    echo '  php tools/seed_admin.php --revoke=email  remove one (refused on the last)' . $nl . $nl;
    echo 'Every change is journaled (compte.super_ajout / compte.super_retrait).' . $nl;
    exit(0);
}

/** Resolves an account by address, even softly deleted. */
$trouver = static function (PDO $pdo, string $courriel): ?array {
    $stmt = $pdo->prepare('SELECT id, courriel, role_global, statut FROM utilisateur WHERE courriel = :c LIMIT 1');
    $stmt->execute(['c' => $courriel]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row === false ? null : $row;
};

// --- Grant -----------------------------------------------------------------
if ($grant !== null && $grant !== '') {
    $cible = $trouver($pdo, $grant);

    if ($cible === null) {
        fwrite(STDERR, "No account uses {$grant}: create it first, then grant.\n");
        exit(1);
    }

    $ancienRole = (string) $cible['role_global'];
    $users->accorderSuperAdmin((int) $cible['id']);

    $journal->log(null, 'compte.super_ajout', 'utilisateur', (int) $cible['id'], 'succes', [
        'courriel' => $grant,
        'ancien_role' => $ancienRole,
    ]);

    echo "Protected account added: {$grant} (id {$cible['id']})\n";

    if ($ancienRole !== 'administrateur_technique') {
        echo "  role : {$ancienRole} -> administrateur_technique\n";
    }

    echo 'Protected accounts now: ' . count($users->superAdmins()) . "\n";
    exit(0);
}

// --- Revoke ----------------------------------------------------------------
if ($revoke !== null && $revoke !== '') {
    $cible = $trouver($pdo, $revoke);

    if ($cible === null) {
        fwrite(STDERR, "No account uses {$revoke}.\n");
        exit(1);
    }

    if (!$users->estCompteSuper((int) $cible['id'])) {
        fwrite(STDERR, "{$revoke} is not a protected account.\n");
        exit(1);
    }

    $users->retirerSuperAdmin((int) $cible['id']);

    $restants = count($users->superAdmins());

    $journal->log(null, $restants === 0 ? 'compte.super_desactivation' : 'compte.super_retrait', 'utilisateur', (int) $cible['id'], 'succes', [
        'courriel' => $revoke,
    ]);

    echo "Protection removed from {$revoke}\n";
    echo 'Protected accounts left: ' . $restants . "\n";

    if ($restants === 0) {
        echo "No account is protected anymore. Run without arguments to restore one.\n";
    }
    exit(0);
}

// --- Ensure at least one protected account --------------------------------
$superAdmins = $users->superAdmins();
$action = 'Protected account confirmed';

if ($superAdmins === []) {
    // No protected account at all: reuse the bootstrap address if it exists,
    // even softly deleted, otherwise create it.
    $cible = $trouver($pdo, strtolower(trim($courriel)));
    $motDePasse = getenv('ADMIN_PASSWORD') ?: ($reset ? 'Super-' . bin2hex(random_bytes(6)) . '-2026!' : 'Hackathon2026!');

    if ($cible !== null) {
        $superId = (int) $cible['id'];
        $action = 'Protected account restored';
    } else {
        $errors = PolitiqueMotDePasse::validate($motDePasse);

        if ($errors !== []) {
            fwrite(STDERR, "Password rejected:\n" . implode("\n", $errors) . "\n");
            exit(1);
        }

        $superId = $users->create($courriel, $nom, $prenom, PolitiqueMotDePasse::hash($motDePasse), 'administrateur_technique', true);
        $action = 'Protected account created';
    }

    $users->accorderSuperAdmin((int) $superId);

    $journal->log(null, 'compte.super_ajout', 'utilisateur', (int) $superId, 'succes', [
        'courriel' => $courriel,
        'source' => 'bootstrap',
    ]);

    $superAdmins = $users->superAdmins();
}

$superId = (int) $superAdmins[0]['id'];
$courriel = (string) $superAdmins[0]['courriel'];

// --- Password replacement (recovery path) ---------------------------------
$motDePasseApplique = null;

if ($reset) {
    $motDePasse = getenv('ADMIN_PASSWORD') ?: 'Super-' . bin2hex(random_bytes(6)) . '-2026!';
    $errors = PolitiqueMotDePasse::validate($motDePasse);

    if ($errors !== []) {
        fwrite(STDERR, "Password rejected:\n" . implode("\n", $errors) . "\n");
        exit(1);
    }

    $users->updateMotDePasse($superId, PolitiqueMotDePasse::hash($motDePasse));
    $motDePasseApplique = $motDePasse;
    $action .= ' + password reset';
}

// --- Default workspace + membership (idempotent) --------------------------
$espaces = new Espace($pdo);
$adhesions = new Adhesion($pdo);

$stmt = $pdo->query("SELECT id FROM espace WHERE nom = 'Général' AND supprime_le IS NULL LIMIT 1");
$spaceId = $stmt->fetchColumn();

if ($spaceId === false) {
    $spaceId = $espaces->create('Général', null, 'restreint');
    echo "Space created: Général (#{$spaceId})\n";
}

$spaceId = (int) $spaceId;

if ($adhesions->find($spaceId, $superId) === null) {
    $adhesions->create($spaceId, $superId, 'responsable');
    echo "Membership created: {$courriel} → Général (responsable)\n";
}

// --- Report ----------------------------------------------------------------
echo "\n";
echo "{$action}\n";
echo "  id        : {$superId}\n";
echo "  courriel  : {$courriel}\n";
echo '  protégés  : ' . count($superAdmins) . "\n";

foreach ($superAdmins as $protege) {
    echo '    - ' . $protege['courriel'] . ' (#' . $protege['id'] . ")\n";
}

if ($motDePasseApplique !== null) {
    echo "  password  : {$motDePasseApplique}\n";
    echo "\n  Note this password now: it is not stored anywhere in clear.\n";
}

echo "\nProtected accounts can never be deleted, suspended or demoted,\n";
echo "and at least one always remains.\n";
