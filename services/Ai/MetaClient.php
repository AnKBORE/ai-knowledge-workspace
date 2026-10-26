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
class MetaClient
{
    /**
     * @param array<string, mixed> $config Configuration from config/meta.php.
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Ask a conversational model and return its text answer.
     *
     * Two API styles are supported:
     *  - "meta"   : POST {base_url}/v1/responses         (Meta Responses API)
     *  - "openai" : POST {base_url}/v1/chat/completions   (Baseten, Modal, Fireworks)
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    public function chat(string $prompt, array $context = [], ?string $model = null): string
    {
        $style = (string) ($this->config['api_style'] ?? 'meta');

        return $style === 'openai'
            ? $this->chatOpenAi($prompt, $context, $model)
            : $this->chatMeta($prompt, $context, $model);
    }

    /**
     * Meta Model API — Responses API format.
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    private function chatMeta(string $prompt, array $context, ?string $model): string
    {
        $input = [];
        foreach ($context as $message) {
            $input[] = [
                'role' => (string) ($message['role'] ?? 'user'),
                'content' => [['type' => 'input_text', 'text' => (string) ($message['content'] ?? '')]],
            ];
        }
        $input[] = ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $prompt]]];

        $payload = $this->request('/v1/responses', [
            'model' => $model ?? (string) ($this->config['chat_model'] ?? 'muse-wonder-1.0'),
            'input' => $input,
            'stream' => false,
        ]);

        return $this->extractOutputText($payload);
    }

    /**
     * OpenAI-compatible endpoint (Baseten, Modal, Fireworks, ...).
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    private function chatOpenAi(string $prompt, array $context, ?string $model): string
    {
        $messages = [];
        foreach ($context as $message) {
            $messages[] = [
                'role' => (string) ($message['role'] ?? 'user'),
                'content' => (string) ($message['content'] ?? ''),
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        $payload = $this->request('/v1/chat/completions', [
            'model' => $model ?? (string) ($this->config['chat_model'] ?? ''),
            'messages' => $messages,
        ]);

        return (string) ($payload['choices'][0]['message']['content'] ?? '');
    }

    /**
     * Extract the assistant text from a Responses API payload.
     *
     * @param array<string, mixed> $payload
     */
    private function extractOutputText(array $payload): string
    {
        $text = '';

        foreach (($payload['output'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            foreach (($item['content'] ?? []) as $part) {
                if (is_array($part) && ($part['type'] ?? '') === 'output_text' && isset($part['text'])) {
                    $text .= (string) $part['text'];
                }
            }
            if (isset($item['text']) && is_string($item['text'])) {
                $text .= $item['text'];
            }
        }

        if ($text === '') {
            $text = (string) ($payload['output_text'] ?? '');
        }

        return $text;
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
     * Transcribe an audio recording with Muse Voice Transcribe.
     *
     * @param string $audioPath Path to a local audio file (wav/mp3/m4a/...).
     */
    public function transcribe(string $audioPath): string
    {
        $payload = $this->requestMultipart(
            (string) ($this->config['transcription_endpoint'] ?? '/v1/audio/transcriptions'),
            $audioPath
        );

        return (string) ($payload['text'] ?? '');
    }

    /**
     * Extract text from an image (scanned document, form, table) with SAM 3.1.
     *
     * @param string $imagePath Path to a local image file (png/jpg/tiff/...).
     */
    public function describeImage(string $imagePath): string
    {
        $data = file_get_contents($imagePath);
        if ($data === false) {
            throw new \RuntimeException('Could not read image: ' . $imagePath);
        }

        $payload = $this->request(
            (string) ($this->config['vision_endpoint'] ?? '/v1/vision'),
            [
                'model' => (string) ($this->config['vision_model'] ?? 'sam-3.1'),
                'image' => base64_encode($data),
            ]
        );

        return (string) ($payload['text'] ?? $payload['description'] ?? '');
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
        $this->applyCaBundle($handle);

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

    /**
     * Perform a multipart POST (file upload) and return the JSON body.
     *
     * @return array<string, mixed>
     */
    private function requestMultipart(string $path, string $filePath): array
    {
        $url = rtrim((string) ($this->config['base_url'] ?? ''), '/') . $path;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (string) ($this->config['api_key'] ?? ''),
            ],
            CURLOPT_POSTFIELDS => ['file' => new \CURLFile($filePath)],
            CURLOPT_TIMEOUT => (int) ($this->config['timeout'] ?? 60),
        ]);
        $this->applyCaBundle($handle);

        $response = curl_exec($handle);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new \RuntimeException('Meta API upload failed: ' . $error);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Meta API returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * Apply the optional CA bundle to a cURL handle (Windows without curl.cainfo).
     */
    private function applyCaBundle($handle): void
    {
        $caBundle = (string) ($this->config['ca_bundle'] ?? '');
        if ($caBundle !== '' && is_file($caBundle)) {
            curl_setopt($handle, CURLOPT_CAINFO, $caBundle);
        }
    }
}
