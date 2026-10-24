<?php

declare(strict_types=1);

/**
 * Meta Model API configuration.
 *
 * Values come from environment variables (loaded from `.env` by the app
 * bootstrap — see core/Env.php in the foundation). No key is hardcoded here,
 * so this file is safe to commit.
 */

return [
    // Meta Model API entry point (confirm the exact URL at the kickoff, 26 Oct).
    'base_url' => getenv('META_MODEL_API_BASE_URL') ?: 'https://api.meta.ai',

    // API key — never committed (see .env).
    'api_key' => getenv('META_MODEL_API_KEY') ?: '',

    // Main conversational model (Muse Spark).
    'chat_model' => getenv('META_MODEL_NAME') ?: 'muse-spark',

    // Advanced model for report generation / decision support (Muse Wonder).
    'advanced_model' => 'muse-wonder',

    // Embedding model (open-weight, e.g. hosted via Baseten / Modal).
    'embedding_model' => getenv('META_EMBEDDING_MODEL') ?: 'open-weight-embedding',

    // Max request time in seconds.
    'timeout' => 60,
];
