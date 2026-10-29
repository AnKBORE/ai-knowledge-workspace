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

    // Long audio is split into chunks shorter than the provider's per-request limit.
    'audio_max_seconds' => (int) (getenv('META_AUDIO_MAX_SECONDS') ?: 600),
    'audio_chunk_seconds' => 300,

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

    // Speech-to-text model + endpoint. Default '' = no dedicated ASR, so the
    // api_style path is used (chat multimodal). Set META_TRANSCRIPTION_MODEL
    // (e.g. a Whisper deployment) to force the /v1/audio/transcriptions path.
    'transcription_model' => getenv('META_TRANSCRIPTION_MODEL') ?: '',
    'transcription_endpoint' => getenv('META_TRANSCRIPTION_ENDPOINT') ?: '/v1/audio/transcriptions',

    // Baseten dedicated Whisper deployment (e.g. Whisper Large V3 Turbo):
    // https://model-{id}.api.baseten.co/environments/production/predict.
    // Needs a key from the organisation that owns the deployment (the
    // hackathon key only serves muse-spark-open-1026). '' = disabled.
    'baseten_whisper_url' => getenv('META_BASETEN_WHISPER_URL') ?: '',
    'baseten_whisper_key' => getenv('META_BASETEN_WHISPER_KEY') ?: '',
    'baseten_whisper_language' => getenv('META_BASETEN_WHISPER_LANGUAGE') ?: '',

    // Local whisper.cpp (free, offline). Set META_WHISPER_BIN + META_WHISPER_MODEL
    // to use it; whisper_args can add -l fr, -t 4, etc. '-nt' = no timestamps.
    'whisper_binary' => getenv('META_WHISPER_BIN') ?: '',
    'whisper_model' => getenv('META_WHISPER_MODEL') ?: '',
    'whisper_args' => getenv('META_WHISPER_ARGS') ?: '-nt',

    // Vision model (SAM 3.1) for scanned documents + endpoint.
    'vision_model' => 'sam-3.1',
    'vision_endpoint' => '/v1/vision',

    // Max request time in seconds.
    'timeout' => 180,
    // OCR of dense scans: the reasoning model can take 2–3 min per page.
    'vision_timeout' => (int) (getenv('META_VISION_TIMEOUT') ?: 300),
];
