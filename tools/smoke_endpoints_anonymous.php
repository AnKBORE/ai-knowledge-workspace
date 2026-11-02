<?php

declare(strict_types=1);

/**
 * Anonymous access sweep over every API endpoint.
 *
 * The endpoints are procedural scripts, so PHPUnit cannot call them; this script
 * probes them against a live instance and asserts the only thing that must never
 * regress: without a session, no endpoint returns data.
 *
 * Usage:
 *   php tools/smoke_endpoints_anonymous.php [base-url]
 *
 * Exit code 0 when every endpoint refuses, 1 otherwise.
 */

$base = rtrim($argv[1] ?? (getenv('SMOKE_BASE_URL') ?: 'http://127.0.0.1:8080'), '/');

/** Sends a request without any cookie and returns the status and the body. */
function probe(string $method, string $url): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
    ]);

    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, '{}');
    }

    $body = (string) curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return ['status' => $status, 'body' => $body];
}

$endpoints = array_map(
    static fn (string $path): string => basename($path),
    glob(dirname(__DIR__) . '/public/api/*.php') ?: []
);

sort($endpoints);

// Keys that would appear in a successful payload. A refusal must never contain them.
$dataKeys = ['"documents"', '"entries"', '"corrections"', '"chunks"', '"url"', '"answer"', '"job_id"'];

$failures = 0;
$rows = [];

foreach ($endpoints as $endpoint) {
    $url = $base . '/api/' . $endpoint;

    foreach (['POST', 'GET'] as $method) {
        $response = probe($method, $url);

        $refused = in_array($response['status'], [401, 403, 405, 419, 422], true);
        $leaks = false;

        foreach ($dataKeys as $key) {
            if (str_contains($response['body'], $key)) {
                $leaks = true;
                break;
            }
        }

        $ok = $refused && !$leaks;
        $failures += $ok ? 0 : 1;
        $rows[] = [$endpoint, $method, (string) $response['status'], $leaks ? 'DONNEES' : '—', $ok ? 'OK' : 'KO'];
    }
}

$widths = [0, 0, 0, 0];

foreach ($rows as $row) {
    foreach (array_slice($row, 0, 4) as $i => $cell) {
        $widths[$i] = max($widths[$i], strlen($cell));
    }
}

foreach ($rows as $row) {
    if ($row[4] === 'OK') {
        continue;
    }

    printf(
        "%-{$widths[0]}s  %-{$widths[1]}s  statut %-{$widths[2]}s  fuite %-{$widths[3]}s  KO\n",
        $row[0],
        $row[1],
        $row[2],
        $row[3]
    );
}

echo "\n";
echo count($endpoints) . ' endpoints, ' . count($rows) . " requêtes anonymes.\n";
echo $failures === 0
    ? "Toutes refusées, aucune donnée exposée.\n"
    : $failures . " requête(s) non conforme(s).\n";

exit($failures === 0 ? 0 : 1);
