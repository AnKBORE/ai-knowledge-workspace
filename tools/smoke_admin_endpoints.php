<?php

declare(strict_types=1);

/**
 * Smoke test for the administration endpoints.
 *
 * The endpoints are procedural scripts (`require bootstrap`, `exit`), so PHPUnit
 * cannot call them without a running server. This script exercises their guard
 * chain against a live instance: anonymous (401), member (403), administrator
 * without the password (422). No data is created, so it is safe to re-run.
 *
 * Usage:
 *   php tools/smoke_admin_endpoints.php
 *
 * Configuration (environment or defaults for a local install):
 *   SMOKE_BASE_URL, SMOKE_ADMIN_EMAIL, SMOKE_ADMIN_PASSWORD,
 *   SMOKE_MEMBER_EMAIL, SMOKE_MEMBER_PASSWORD
 */

$base = rtrim(getenv('SMOKE_BASE_URL') ?: 'http://127.0.0.1:8080', '/');
$adminEmail = getenv('SMOKE_ADMIN_EMAIL') ?: 'admin@super.bf';
$adminPassword = getenv('SMOKE_ADMIN_PASSWORD') ?: '@Admin2026!super';
$memberEmail = getenv('SMOKE_MEMBER_EMAIL') ?: 'membre.demo@local';
$memberPassword = getenv('SMOKE_MEMBER_PASSWORD') ?: 'MembreDemo2026!';

/**
 * One HTTP exchange with a cookie jar.
 *
 * @param list<string> $fields
 * @return array{status:int, body:string, location:string}
 */
function http(string $method, string $url, string $jar, array $fields = [], bool $follow = false): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Accept: application/json'],
    ]);

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
    }

    $body = (string) curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $location = (string) (curl_getinfo($curl, CURLINFO_REDIRECT_URL) ?: '');
    curl_close($curl);

    return ['status' => $status, 'body' => $body, 'location' => $location];
}

/** Logs in and returns the CSRF token of a page containing a form. */
function login(string $base, string $jar, string $email, string $password, string $page): ?string
{
    @unlink($jar);
    $html = http('GET', $base . '/login', $jar)['body'];

    if (!preg_match('/name="_csrf" value="([^"]+)"/', $html, $m)) {
        return null;
    }

    http('POST', $base . '/login', $jar, [
        '_csrf' => $m[1],
        'courriel' => $email,
        'mot_de_passe' => $password,
    ]);

    $page = http('GET', $base . $page, $jar)['body'];

    return preg_match('/name="_csrf" value="([^"]+)"/', $page, $m) ? $m[1] : null;
}

$endpoints = [
    'admin_user_save.php',
    'admin_user_statut.php',
    'admin_user_delete.php',
    'admin_user_role.php',
    'admin_user_super.php',
    'admin_espace_save.php',
    'admin_espace_statut.php',
    'admin_adhesion_save.php',
    'admin_adhesion_end.php',
];

$tmp = sys_get_temp_dir() . '/aks-smoke-' . bin2hex(random_bytes(4));
$anonymousJar = $tmp . '-anon.txt';
$memberJar = $tmp . '-member.txt';
$adminJar = $tmp . '-admin.txt';

$memberToken = login($base, $memberJar, $memberEmail, $memberPassword, '/mon-profil');
$adminToken = login($base, $adminJar, $adminEmail, $adminPassword, '/administration');

if ($memberToken === null || $adminToken === null) {
    fwrite(STDERR, "Could not sign in: check SMOKE_* credentials and that the server is running at {$base}.\n");
    exit(2);
}

// admin_user_super is only reachable by a protected account: for anyone else
// the expected answer is 403, not 422. Detect it from the administration page.
$adminIsSuper = str_contains(
    http('GET', $base . '/administration', $adminJar)['body'],
    'admin_user_super'
);

$probes = [
    ['anonymous', $anonymousJar, [], 401],
    ['member', $memberJar, ['_csrf' => $memberToken], 403],
    ['admin, no password', $adminJar, ['_csrf' => $adminToken], 422],
];

if (!$adminIsSuper) {
    echo "Note: the configured administrator is not a Super Admin; "
        . "admin_user_super is expected to answer 403.\n\n";
}

$failures = 0;
$rows = [];

foreach ($endpoints as $endpoint) {
    foreach ($probes as [$label, $jar, $fields, $expected]) {
        // A non-protected administrator is refused by admin_user_super before
        // the password is even looked at.
        if ($endpoint === 'admin_user_super.php' && !$adminIsSuper && $label === 'admin, no password') {
            $expected = 403;
        }

        $response = http('POST', $base . '/api/' . $endpoint, $jar, $fields);
        $ok = $response['status'] === $expected;
        $failures += $ok ? 0 : 1;
        $rows[] = [$endpoint, $label, (string) $expected, (string) $response['status'], $ok ? 'OK' : 'KO'];
    }
}

$widths = [0, 0, 0, 0];

foreach ($rows as $row) {
    foreach (array_slice($row, 0, 4) as $i => $cell) {
        $widths[$i] = max($widths[$i], strlen($cell));
    }
}

foreach ($rows as $row) {
    printf(
        "%-{$widths[0]}s  %-{$widths[1]}s  attendu %-{$widths[2]}s  obtenu %-{$widths[3]}s  %s\n",
        $row[0],
        $row[1],
        $row[2],
        $row[3],
        $row[4]
    );
}

@unlink($anonymousJar);
@unlink($memberJar);
@unlink($adminJar);

echo "\n";
echo $failures === 0
    ? count($rows) . " verifications, tout est conforme.\n"
    : $failures . " verification(s) en echec sur " . count($rows) . ".\n";

exit($failures === 0 ? 0 : 1);
