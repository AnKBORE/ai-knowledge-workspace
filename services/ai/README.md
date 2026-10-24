# AI layer (services/ai)

Generic, self-contained services for the Meta Model API integration and the
RAG (Retrieval-Augmented Generation) pipeline. No client-specific code and no
secrets — API keys are read from `.env` only.

## Components

| File | Role |
|---|---|
| `MetaClient.php` | Low-level HTTP client for the Meta Model API (chat + embeddings) |
| `EmbeddingService.php` | Vector helpers: embed text, cosine similarity |
| `RagService.php` | RAG pipeline: chunking, indexing, retrieval, generation |
| `../../controllers/AiAssistantController.php` | HTTP entry point (JSON in / JSON out) |

## Configuration

- `config/meta.php` — API base URL, model names, timeout.
- `.env` — `META_MODEL_API_BASE_URL`, `META_MODEL_API_KEY`, `META_MODEL_NAME`,
  `META_EMBEDDING_MODEL`. Fill these with the keys provided at the kickoff.

## Wiring example

```php
$config = require __DIR__ . '/../../config/meta.php';

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=ai_knowledge_workspace;charset=utf8mb4',
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$client     = new App\Services\Ai\MetaClient($config);
$embeddings = new App\Services\Ai\EmbeddingService($client);
$rag        = new App\Services\Ai\RagService($client, $embeddings, $pdo);

$answer = $rag->query('Which training projects were carried out in 2025?');
```

## Endpoints

The exact API paths (`/v1/chat/completions`, `/v1/embeddings`) are placeholders
to confirm against the official Meta Model API documentation at the kickoff
(26 October). Update `MetaClient::request()` if the paths differ.
