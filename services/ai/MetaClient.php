<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Thin HTTP client for the Meta Model API.
 *
 * Wraps the low-level cURL calls so the rest of the application only deals
 * with plain PHP arrays and strings. The exact endpoint paths are confirmed
 * against the official Meta Model API at the hackathon kickoff; adjust
 * `request()` if the paths differ.
 */
final class MetaClient
{
    /**
     * @param array<string, mixed> $config Configuration from config/meta.php.
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Ask a conversational model (Muse Spark) and return its text answer.
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    public function chat(string $prompt, array $context = [], ?string $model = null): string
    {
        $messages = [...$context, ['role' => 'user', 'content' => $prompt]];

        $payload = $this->request('/v1/chat/completions', [
            'model' => $model ?? (string) ($this->config['chat_model'] ?? 'muse-spark'),
            'messages' => $messages,
        ]);

        return (string) ($payload['choices'][0]['message']['content'] ?? '');
    }

    /**
     * Convert a text into a numeric embedding vector.
     *
     * @return list<float>
     */
    public function embed(string $text): array
    {
        $payload = $this->request('/v1/embeddings', [
            'model' => (string) ($this->config['embedding_model'] ?? 'open-weight-embedding'),
            'input' => $text,
        ]);

        $embedding = $payload['data'][0]['embedding'] ?? [];

        return is_array($embedding) ? array_map('floatval', $embedding) : [];
    }

    /**
     * Perform a POST request to the Meta Model API and return the JSON body.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function request(string $path, array $body): array
    {
        $url = rtrim((string) ($this->config['base_url'] ?? ''), '/') . $path;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . (string) ($this->config['api_key'] ?? ''),
            ],
            CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => (int) ($this->config['timeout'] ?? 60),
        ]);

        $response = curl_exec($handle);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new \RuntimeException('Meta API request failed: ' . $error);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Meta API returned invalid JSON.');
        }

        return $decoded;
    }
}
