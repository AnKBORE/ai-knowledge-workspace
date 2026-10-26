<?php

declare(strict_types=1);

/**
 * Application-level configuration (product name, base URL, locale).
 */
return [
    'name' => getenv('APP_NAME') ?: 'Agentic Knowledge Workspace',
    'url' => getenv('APP_URL') ?: 'http://localhost/ai-knowledge-workspace/public',
    'locale' => getenv('APP_LOCALE') ?: 'fr',
    'version' => '1.0.0',
];
