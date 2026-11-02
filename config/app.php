<?php

declare(strict_types=1);

/**
 * Application-level configuration (product name, base URL, locale).
 */
return [
    'name' => getenv('APP_NAME') ?: 'Agentic Knowledge Workspace',
    'url' => getenv('APP_URL') ?: 'http://localhost/ai-knowledge-workspace/public',
    'locale' => getenv('APP_LOCALE') ?: 'en',
    'version' => '1.0.0',

    // Published on the landing page. The single place to update when the
    // suite grows: audit_numbers.php fails if the page drifts from these.
    'tests' => ['count' => 124, 'assertions' => 295],
];
