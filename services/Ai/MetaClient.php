<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\AudioSplitter;

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
     * A low temperature (0.2 by default) keeps answers precise and stable.
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    public function chat(string $prompt, array $context = [], ?string $model = null, ?float $temperature = null): string
    {
        $style = (string) ($this->config['api_style'] ?? 'meta');

        return $style === 'openai'
            ? $this->chatOpenAi($prompt, $context, $model, $temperature)
            : $this->chatMeta($prompt, $context, $model, $temperature);
    }

    /**
     * Meta Model API — Responses API format.
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    private function chatMeta(string $prompt, array $context, ?string $model, ?float $temperature): string
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
            'temperature' => $temperature ?? 0.2,
        ]);

        return $this->extractOutputText($payload);
    }

    /**
     * OpenAI-compatible endpoint (Baseten, Modal, Fireworks, ...).
     *
     * @param array<int, array{role: string, content: string}> $context
     */
    private function chatOpenAi(string $prompt, array $context, ?string $model, ?float $temperature): string
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
            'temperature' => $temperature ?? 0.2,
        ]);

        $content = (string) ($payload['choices'][0]['message']['content'] ?? '');

        return trim((string) preg_replace('/<think>.*?<\/think>/s', '', $content));
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
     * Transcribe an audio recording.
     *
     * With an OpenAI-compatible partner (Baseten), Muse Spark Open handles the
     * audio directly; recordings longer than the provider's per-request limit
     * are split into chunks and concatenated. With the Meta Model API, the
     * dedicated endpoint is used.
     *
     * @param string $audioPath Path to a local audio file (wav/mp3/m4a/...).
     */
    public function transcribe(string $audioPath): string
    {
        // Verbatim backends first (local whisper.cpp, Baseten Whisper deployment):
        // on failure or empty output, fall through to the next backend instead
        // of failing the upload.
        $backends = [
            'whisper_binary' => fn () => $this->transcribeLocal($audioPath),
            'baseten_whisper_url' => fn () => $this->transcribeBasetenWhisper($audioPath),
        ];
        foreach ($backends as $key => $backend) {
            if (($this->config[$key] ?? '') === '') {
                continue;
            }
            try {
                $text = $backend();
                if (trim($text) !== '') {
                    return $text;
                }
            } catch (\Throwable $e) {
                error_log('[transcription] ' . $key . ' failed, falling back: ' . $e->getMessage());
            }
        }

        // Dedicated ASR endpoint (Whisper-style /v1/audio/transcriptions):
        // verbatim transcription without LLM copyright guardrails.
        if (($this->config['transcription_model'] ?? '') !== '') {
            return $this->transcribeDedicated($audioPath);
        }

        if ((string) ($this->config['api_style'] ?? 'meta') === 'openai') {
            return $this->transcribeOpenAi($audioPath);
        }

        $payload = $this->requestMultipart(
            (string) ($this->config['transcription_endpoint'] ?? '/v1/audio/transcriptions'),
            $audioPath
        );

        return (string) ($payload['text'] ?? '');
    }

    /**
     * Split long audio (MP3/WAV) into chunks shorter than 5 minutes, transcribe
     * each chunk and concatenate the transcripts.
     */
    private function transcribeOpenAi(string $audioPath): string
    {
        $chunkSeconds = (int) ($this->config['audio_chunk_seconds'] ?? 300);
        $splitter = new AudioSplitter();
        $segments = $splitter->split($audioPath, $chunkSeconds);

        $texts = [];
        foreach ($segments as $segment) {
            $texts[] = $this->chatWithAudio(
                'You are an automatic speech recognition (ASR) engine, not a conversational assistant. '
                    . 'Transcribe the exact words heard in this audio (speech or sung lyrics), in their original language. '
                    . 'Output ONLY the transcript text: no commentary, no description, no refusal, no disclaimer. '
                    . 'This recording was uploaded by its owner for personal documentation.',
                $segment,
            );

            if ($segment !== $audioPath) {
                @unlink($segment);
            }
        }

        return trim(implode("\n\n", $texts));
    }

    /**
     * Baseten dedicated Whisper deployment (predict API):
     * {"whisper_input": {"audio": {"audio_b64"}, "whisper_params"}} → segments[].text.
     */
    private function transcribeBasetenWhisper(string $audioPath): string
    {
        $data = file_get_contents($audioPath);
        if ($data === false) {
            throw new \RuntimeException('Could not read audio: ' . $audioPath);
        }

        $input = ['audio' => ['audio_b64' => base64_encode($data)]];
        $language = (string) ($this->config['baseten_whisper_language'] ?? '');
        if ($language !== '') {
            $input['whisper_params'] = ['audio_language' => $language];
        }

        $payload = $this->request(
            '',
            ['whisper_input' => $input],
            (string) $this->config['baseten_whisper_url'],
            (string) (($this->config['baseten_whisper_key'] ?? '') ?: ($this->config['api_key'] ?? '')),
        );

        $texts = [];
        foreach (($payload['segments'] ?? []) as $segment) {
            if (is_array($segment) && trim((string) ($segment['text'] ?? '')) !== '') {
                $texts[] = trim((string) $segment['text']);
            }
        }
        $text = trim(implode(' ', $texts)) ?: trim((string) ($payload['text'] ?? ''));

        if ($text === '') {
            throw new \RuntimeException('Empty transcription response from the Whisper deployment.');
        }

        return $text;
    }

    /**
     * Local whisper.cpp transcription (free, offline, verbatim).
     * Config: whisper_binary + whisper_model (+ optional whisper_args).
     */
    private function transcribeLocal(string $audioPath): string
    {
        $bin = (string) $this->config['whisper_binary'];
        $model = (string) ($this->config['whisper_model'] ?? '');

        if (!is_file($bin)) {
            throw new \RuntimeException('Whisper binary not found: ' . $bin);
        }
        if ($model === '' || !is_file($model)) {
            throw new \RuntimeException('Whisper model not found: ' . $model);
        }

        $args = trim((string) ($this->config['whisper_args'] ?? '-nt'));
        $cmd = sprintf('"%s" -m "%s" -f "%s" %s', $bin, $model, $audioPath, $args);
        exec($cmd . ' 2>&1', $lines, $code);

        if ($code !== 0) {
            throw new \RuntimeException('whisper.cpp failed: ' . implode(' ', $lines));
        }

        // whisper-cli merges diagnostics (load_backend, whisper_model_load,
        // timings…) into the captured output: keep only transcript lines.
        $transcript = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(load_|whisper_|ggml_|read_audio|system_info|main:|print_|fall|sample|encode|decode|batchd|prompt|total|mel|\s*$)/i', $line)) {
                continue;
            }
            $line = (string) preg_replace('/^\[[^\]]*\]\s*/', '', $line);
            if ($line !== '') {
                $transcript[] = $line;
            }
        }

        return trim(implode("\n", $transcript));
    }

    /**
     * Dedicated speech-to-text endpoint (Whisper-compatible
     * /v1/audio/transcriptions: multipart file + model fields). Long audio is
     * split into provider-sized chunks and concatenated.
     */
    private function transcribeDedicated(string $audioPath): string
    {
        $chunkSeconds = (int) ($this->config['audio_chunk_seconds'] ?? 300);
        $splitter = new AudioSplitter();
        $segments = $splitter->split($audioPath, $chunkSeconds);
        $model = (string) $this->config['transcription_model'];

        $texts = [];
        foreach ($segments as $segment) {
            $payload = $this->requestMultipart(
                (string) ($this->config['transcription_endpoint'] ?? '/v1/audio/transcriptions'),
                $segment,
                ['model' => $model],
            );
            $texts[] = (string) ($payload['text'] ?? '');

            if ($segment !== $audioPath) {
                @unlink($segment);
            }
        }

        return trim(implode("\n\n", $texts));
    }

    /**
     * Extract text from an image (scanned document, form, table).
     *
     * With an OpenAI-compatible partner (Baseten), Muse Spark Open handles the
     * image directly; with the Meta Model API, the dedicated endpoint is used.
     *
     * @param string $imagePath Path to a local image file (png/jpg/tiff/...).
     */
    public function describeImage(string $imagePath): string
    {
        if ((string) ($this->config['api_style'] ?? 'meta') === 'openai') {
            return $this->chatWithImage(
                'Extract all the text visible in this document image. Return only the extracted text, in reading order, without commentary.',
                $imagePath,
            );
        }

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
     * OpenAI-compatible multimodal call with an image (base64 data URL).
     */
    private function chatWithImage(string $prompt, string $imagePath): string
    {
        $data = file_get_contents($imagePath);
        if ($data === false) {
            throw new \RuntimeException('Could not read image: ' . $imagePath);
        }

        $mime = mime_content_type($imagePath) ?: 'image/png';
        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($data);

        $payload = $this->request('/v1/chat/completions', [
            'model' => (string) ($this->config['chat_model'] ?? ''),
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                ],
            ]],
            'temperature' => 0.2,
        ]);

        return (string) ($payload['choices'][0]['message']['content'] ?? '');
    }

    /**
     * OpenAI-compatible multimodal call with an audio file (base64 data URL).
     *
     * Baseten expects the `audio_url` content type (WAV/MP3/FLAC/OGG/M4A).
     */
    private function chatWithAudio(string $prompt, string $audioPath): string
    {
        $data = file_get_contents($audioPath);
        if ($data === false) {
            throw new \RuntimeException('Could not read audio: ' . $audioPath);
        }

        $extension = strtolower(pathinfo($audioPath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'mp3' => 'audio/mpeg',
            'flac' => 'audio/flac',
            'ogg' => 'audio/ogg',
            'm4a', 'aac' => 'audio/mp4',
            default => 'audio/wav',
        };
        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($data);

        $payload = $this->request('/v1/chat/completions', [
            'model' => (string) ($this->config['chat_model'] ?? ''),
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'audio_url', 'audio_url' => ['url' => $dataUrl]],
                ],
            ]],
            'temperature' => 0.2,
        ]);

        $content = (string) ($payload['choices'][0]['message']['content'] ?? '');
        if (trim($content) === '') {
            throw new \RuntimeException('Empty transcription response from the model (unsupported audio format?).');
        }

        // Guardrail refusals (e.g. sung lyrics flagged as copyrighted) must never
        // be indexed as if they were the audio content.
        if (preg_match('/\b(I\s+(can\'?t|cannot|can’t|am\s+unable\s+to|won\'?t)\s+(provide|transcribe|share|help\s+with)|verbatim\s+transcript\s+of\s+(these|this|copyrighted)|copyrighted\s+(song|lyrics|material)|je\s+ne\s+(peux|suis)\s+pas\s+(en\s+mesure\s+de\s+)?(fournir|transcrire))/iu', $content) === 1) {
            throw new \RuntimeException('Transcription refused by the model guardrails (likely copyrighted sung lyrics). Speech recordings are supported; for songs, configure the local whisper.cpp backend.');
        }

        return trim((string) preg_replace('/<think>.*?<\/think>/s', '', $content));
    }

    /**
     * Perform a POST request to the Meta Model API and return the JSON body.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function request(string $path, array $body, ?string $url = null, ?string $apiKey = null): array
    {
        $url ??= rtrim((string) ($this->config['base_url'] ?? ''), '/') . $path;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ($apiKey ?? (string) ($this->config['api_key'] ?? '')),
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

        if (isset($decoded['error'])) {
            $error = $decoded['error'];
            $message = is_array($error)
                ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE))
                : (string) $error;
            throw new \RuntimeException('API error: ' . $message);
        }

        return $decoded;
    }

    /**
     * Perform a multipart POST (file upload) and return the JSON body.
     *
     * @return array<string, mixed>
     */
    /**
     * @param array<string, string> $fields Extra multipart form fields
     *                                      (e.g. ['model' => 'whisper-1']).
     */
    private function requestMultipart(string $path, string $filePath, array $fields = []): array
    {
        $url = rtrim((string) ($this->config['base_url'] ?? ''), '/') . $path;

        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . (string) ($this->config['api_key'] ?? ''),
            ],
            CURLOPT_POSTFIELDS => $fields + ['file' => new \CURLFile($filePath)],
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
