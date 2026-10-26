<?php

declare(strict_types=1);

use App\Core\Env;
use App\Services\Ai\EmbeddingService;
use App\Services\Ai\MetaClient;
use App\Services\Ai\RagService;
use App\Services\Ai\TranscriptionService;
use App\Services\Ai\VisionService;

/**
 * Application bootstrap: load the environment and wire the AI services.
 *
 * Returns an array of dependencies. Both `public/api/ask.php` and
 * `tools/index_document.php` require this file.
 *
 * @return array{config: array<string, mixed>, pdo: PDO, meta: MetaClient, rag: RagService, transcription: TranscriptionService, vision: VisionService}
 */

$root = __DIR__;

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');
require $root . '/core/helpers.php';

$config = require $root . '/config/meta.php';

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
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ],
);

$metaClient = new MetaClient($config);
$embeddings = new EmbeddingService($metaClient);
$rag = new RagService($metaClient, $embeddings, $pdo);
$transcription = new TranscriptionService($metaClient);
$vision = new VisionService($metaClient);

return [
    'config' => $config,
    'pdo' => $pdo,
    'meta' => $metaClient,
    'rag' => $rag,
    'transcription' => $transcription,
    'vision' => $vision,
];
