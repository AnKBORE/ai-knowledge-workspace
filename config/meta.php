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
    // API entry point. For a partner (Baseten/Modal/Fireworks) use its base URL.
    'base_url' => getenv('META_MODEL_API_BASE_URL') ?: 'https://api.meta.ai',

    // "meta" = /v1/responses (Meta) ; "openai" = /v1/chat/completions (partners).
    'api_style' => getenv('META_API_STYLE') ?: 'meta',

    // Optional CA bundle (needed on Windows when curl.cainfo is not set).
    'ca_bundle' => getenv('META_CA_BUNDLE') ?: '',

    // API key — never committed (see .env).
    'api_key' => getenv('META_MODEL_API_KEY') ?: '',

    // Main conversational model (Muse Wonder, per the live API example).
    'chat_model' => getenv('META_MODEL_NAME') ?: 'muse-wonder-1.0',

    // Advanced model for report generation / decision support.
    'advanced_model' => 'muse-wonder-1.0',

    // Embedding model + mode. 'local' = hashing embedding (no endpoint needed);
    // 'api' = use the Meta embeddings endpoint.
    'embedding_model' => getenv('META_EMBEDDING_MODEL') ?: 'open-weight-embedding',
    'embedding_mode' => getenv('META_EMBEDDING_MODE') ?: 'local',

    // Speech-to-text model (Muse Voice Transcribe) + endpoint.
    'transcription_model' => 'muse-voice-transcribe',
    'transcription_endpoint' => '/v1/audio/transcriptions',

    // Vision model (SAM 3.1) for scanned documents + endpoint.
    'vision_model' => 'sam-3.1',
    'vision_endpoint' => '/v1/vision',

    // Max request time in seconds.
    'timeout' => 60,
];
