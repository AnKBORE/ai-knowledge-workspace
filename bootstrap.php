<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Session;
use App\Models\Adhesion;
use App\Models\Document;
use App\Models\Espace;
use App\Models\TentativeConnexion;
use App\Models\Utilisateur;
use App\Models\VersionDocument;
use App\Services\Ai\EmbeddingService;
use App\Services\AuthentificationService;
use App\Services\DocumentService;
use App\Services\JournalService;
use App\Services\PermissionService;
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
require $root . '/core/i18n.php';

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

$session = new Session();
if (PHP_SAPI !== 'cli') {
    $session->start();
}
$csrf = new Csrf($session);
$auth = new Auth($session);
$users = new Utilisateur($pdo);
$tentatives = new TentativeConnexion($pdo);
$journal = new JournalService($pdo);
$authService = new AuthentificationService($users, $tentatives, $auth, $journal);
$espaces = new Espace($pdo);
$adhesions = new Adhesion($pdo);
$permissions = new PermissionService($pdo);
$documentModel = new Document($pdo);
$versionModel = new VersionDocument($pdo);
$documentService = new DocumentService($documentModel, $versionModel, $root . '/storage/files');

$metaClient = new MetaClient($config);
$embeddings = new EmbeddingService($metaClient, (string) ($config['embedding_mode'] ?? 'local'));
$rag = new RagService($metaClient, $embeddings, $pdo, $permissions, (string) $session->get('lang', 'en'));
$transcription = new TranscriptionService($metaClient);
$vision = new VisionService($metaClient);

return [
    'config' => $config,
    'pdo' => $pdo,
    'session' => $session,
    'csrf' => $csrf,
    'auth' => $auth,
    'auth_service' => $authService,
    'journal' => $journal,
    'permissions' => $permissions,
    'espaces' => $espaces,
    'adhesions' => $adhesions,
    'document_model' => $documentModel,
    'version_model' => $versionModel,
    'categorie_model' => new \App\Models\Categorie($pdo),
    'documents_service' => $documentService,
    'users' => $users,
    'storage_path' => $root . '/storage/files',
    'meta' => $metaClient,
    'rag' => $rag,
    'transcription' => $transcription,
    'vision' => $vision,
];
