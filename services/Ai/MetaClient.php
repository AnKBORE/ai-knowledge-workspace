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
    public function transcribe(string $audioPath, ?string $lang = null): string
    {
        // Ordered cascade: each backend that fails (refusal, missing binary,
        // empty output) is logged and the next one is tried; the last error is
        // rethrown only if every configured backend failed.
        $verbatim = [
            'whisper_binary' => fn () => $this->transcribeLocal($audioPath, $lang),
            'baseten_whisper_url' => fn () => $this->transcribeBasetenWhisper($audioPath, $lang),
            'transcription_model' => fn () => $this->transcribeDedicated($audioPath),
        ];
        $cloud = (string) ($this->config['api_style'] ?? 'meta') === 'openai'
            ? fn () => $this->transcribeOpenAi($audioPath, $lang)
            : fn () => (string) ($this->requestMultipart(
                (string) ($this->config['transcription_endpoint'] ?? '/v1/audio/transcriptions'),
                $audioPath,
            )['text'] ?? '');

        $backends = array_filter($verbatim, fn (string $key) => ($this->config[$key] ?? '') !== '', ARRAY_FILTER_USE_KEY);
        // 'cloud' (default): Muse Spark Open first — measured 8–18 s with exact
        // transcripts vs ~2 min for whisper.cpp on CPU; verbatim backends then
        // cover what the model declines (sung lyrics). 'local': the reverse.
        $backends = (string) ($this->config['asr_priority'] ?? 'cloud') === 'local'
            ? $backends + ['muse' => $cloud]
            : ['muse' => $cloud] + $backends;

        $lastError = null;
        foreach ($backends as $name => $backend) {
            try {
                $text = trim($backend());
                if ($text !== '') {
                    return $text;
                }
            } catch (\Throwable $e) {
                $lastError = $e;
                error_log('[transcription] ' . $name . ' failed, trying next backend: ' . $e->getMessage());
            }
        }

        throw $lastError ?? new \RuntimeException('Empty transcription response from every backend.');
    }

    /**
     * Split long audio (MP3/WAV) into chunks shorter than 5 minutes, transcribe
     * each chunk and concatenate the transcripts.
     */
    private function transcribeOpenAi(string $audioPath, ?string $lang = null): string
    {
        $chunkSeconds = (int) ($this->config['audio_chunk_seconds'] ?? 300);
        $splitter = new AudioSplitter();
        $segments = $splitter->split($audioPath, $chunkSeconds);

        $langHint = $lang !== null
            ? ' The audio language is ' . ($lang === 'mo' ? 'Mooré (Mossi, Burkina Faso) — transcribe the phonetic Mooré words as they sound, using Mooré orthography characters (ɩ ʋ ã ẽ ĩ õ ũ ñ) when needed' : ($lang === 'fr' ? 'French' : 'English')) . '.'
            : '';
        $texts = [];
        foreach ($segments as $segment) {
            $texts[] = $this->chatWithAudio(
                'Transcribe the exact words spoken in this audio, in their original language. Output only the transcript.' . $langHint,
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
    private function transcribeBasetenWhisper(string $audioPath, ?string $lang = null): string
    {
        $data = file_get_contents($audioPath);
        if ($data === false) {
            throw new \RuntimeException('Could not read audio: ' . $audioPath);
        }

        $input = ['audio' => ['audio_b64' => base64_encode($data)]];
        $language = $this->whisperLang($lang) ?? (string) ($this->config['baseten_whisper_language'] ?? '');
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
    private function transcribeLocal(string $audioPath, ?string $lang = null): string
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
        $wl = $this->whisperLang($lang);
        if ($wl !== null) {
            $args = trim((string) preg_replace('/(^|\s)-l\s+\S+/', ' ', $args)) . ' -l ' . $wl;
        }
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
     * Local whisper.cpp transcription WITH millisecond timestamps — powers the
     * karaoke-style live translation in the documents audio modal.
     *
     * @return list<array{start:int, end:int, text:string}> offsets in ms
     */
    public function transcribeTimedLocal(string $audioPath, string $outputBase): array
    {
        $segments = $this->whisperTimedRun($audioPath, $outputBase, null);

        // Mooré is absent from whisper's 99 languages: on Mooré audio, language
        // autodetect hallucinates non-Latin scripts (Sinhala, Tibetan). French
        // phonetics are the closest match — retry with -l fr, then keep the
        // better of the two.
        if ($this->latinShare(implode(' ', array_column($segments, 'text'))) < 0.55) {
            $retry = $this->whisperTimedRun($audioPath, $outputBase . '_fr', 'fr');
            if ($this->latinShare(implode(' ', array_column($retry, 'text')))
                > $this->latinShare(implode(' ', array_column($segments, 'text')))) {
                $segments = $retry;
            }
        }

        return $segments;
    }

    private function whisperTimedRun(string $audioPath, string $outputBase, ?string $language): array
    {
        $bin = (string) $this->config['whisper_binary'];
        $model = (string) ($this->config['whisper_model'] ?? '');

        if (!is_file($bin)) {
            throw new \RuntimeException('Whisper binary not found: ' . $bin);
        }
        if ($model === '' || !is_file($model)) {
            throw new \RuntimeException('Whisper model not found: ' . $model);
        }

        // -nt suppresses timestamps in every output format — strip it so the
        // JSON offsets are populated. A forced -l replaces any -l in the args.
        $args = trim((string) preg_replace('/(^|\s)-n[tph]\b/', ' ', (string) ($this->config['whisper_args'] ?? '')));
        if ($language !== null) {
            $args = trim((string) preg_replace('/(^|\s)-l\s+\S+/', ' ', $args)) . ' -l ' . $language;
        }

        $cmd = sprintf('"%s" -m "%s" -f "%s" -oj -of "%s" %s', $bin, $model, $audioPath, $outputBase, $args);
        exec($cmd . ' 2>&1', $lines, $code);

        $jsonPath = $outputBase . '.json';
        $json = is_file($jsonPath) ? (string) file_get_contents($jsonPath) : '';

        if ($code !== 0 || $json === '') {
            throw new \RuntimeException('whisper.cpp timed transcription failed.');
        }

        $data = json_decode($json, true);
        $segments = [];
        foreach ((array) ($data['transcription'] ?? []) as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $segments[] = [
                'start' => (int) ($row['offsets']['from'] ?? 0),
                'end' => (int) ($row['offsets']['to'] ?? 0),
                'text' => $text,
            ];
        }

        return $segments;
    }

    /**
     * Map our lang codes to whisper's ISO codes. Mooré is unsupported by
     * whisper — French phonetics are the closest proxy (proven on real audio).
     */
    private function whisperLang(?string $lang): ?string
    {
        return match ($lang) {
            'fr' => 'fr',
            'en' => 'en',
            'mo' => 'fr',
            default => null,
        };
    }

    /** Share of Latin-script letters — detects non-Latin ASR hallucinations. */
    private function latinShare(string $text): float
    {
        $text = (string) preg_replace('/[\s\p{P}\p{N}]+/u', '', $text);
        if ($text === '') {
            return 0.0;
        }

        return preg_match_all('/\p{Latin}/u', $text) / mb_strlen($text);
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
            return $this->chatWithImage(self::OCR_PROMPT, $imagePath);
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
     * OCR several images (e.g. the pages of a scanned PDF). With an
     * OpenAI-compatible partner the requests run in parallel (curl_multi), so
     * total latency ≈ the slowest page instead of the sum of all pages.
     *
     * @param list<string> $imagePaths
     * @return list<string> extracted text, in the same order
     */
    public function describeImages(array $imagePaths): array
    {
        if ((string) ($this->config['api_style'] ?? 'meta') !== 'openai' || count($imagePaths) < 2) {
            return array_map(fn (string $path) => $this->describeImage($path), $imagePaths);
        }

        $url = rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/v1/chat/completions';
        $multi = curl_multi_init();
        $handles = [];
        foreach ($imagePaths as $i => $path) {
            $handles[$i] = curl_init($url);
            curl_setopt_array($handles[$i], [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . (string) ($this->config['api_key'] ?? ''),
                ],
                CURLOPT_POSTFIELDS => json_encode($this->imageChatBody(self::OCR_PROMPT, $path), JSON_THROW_ON_ERROR),
                CURLOPT_TIMEOUT => (int) ($this->config['vision_timeout'] ?? 300),
            ]);
            $this->applyCaBundle($handles[$i]);
            curl_multi_add_handle($multi, $handles[$i]);
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi);
            }
        } while ($running && $status === CURLM_OK);

        $texts = [];
        foreach ($handles as $i => $handle) {
            $payload = json_decode((string) curl_multi_getcontent($handle), true);
            $error = curl_error($handle);
            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);

            $content = is_array($payload) ? (string) ($payload['choices'][0]['message']['content'] ?? '') : '';
            if ($content === '') {
                // One failed page must not lose the others: retry it sequentially.
                error_log('[vision] parallel OCR failed for page ' . ($i + 1) . ': ' . ($error ?: 'empty response') . ' — retrying');
                $content = $this->describeImage($imagePaths[$i]);
            }
            $texts[] = trim((string) preg_replace('/<think>.*?<\/think>/s', '', $content));
        }
        curl_multi_close($multi);

        return $texts;
    }

    private const OCR_PROMPT = 'Extract all the text visible in this document image. Return only the extracted text, in reading order, without commentary.';

    /**
     * Chat-completions body carrying one image (downscaled, base64 data URL).
     *
     * @return array<string, mixed>
     */
    private function imageChatBody(string $prompt, string $imagePath): array
    {
        $data = file_get_contents($imagePath);
        if ($data === false) {
            throw new \RuntimeException('Could not read image: ' . $imagePath);
        }

        $mime = mime_content_type($imagePath) ?: 'image/png';
        [$data, $mime] = $this->downscaleImage($data, $mime);

        return [
            'model' => (string) ($this->config['chat_model'] ?? ''),
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'image_url', 'image_url' => ['url' => 'data:' . $mime . ';base64,' . base64_encode($data)]],
                ],
            ]],
            'temperature' => 0.2,
            // Transcribing visible text needs little reasoning: measured −64 %
            // completion tokens for an equivalent transcript ('' = provider default).
        ] + (($effort = (string) ($this->config['vision_reasoning_effort'] ?? '')) !== '' ? ['reasoning_effort' => $effort] : []);
    }

    /**
     * OpenAI-compatible multimodal call with an image (base64 data URL).
     */
    private function chatWithImage(string $prompt, string $imagePath): string
    {
        $payload = $this->request('/v1/chat/completions', $this->imageChatBody($prompt, $imagePath), timeout: (int) ($this->config['vision_timeout'] ?? 300));
        $content = (string) ($payload['choices'][0]['message']['content'] ?? '');

        return trim((string) preg_replace('/<think>.*?<\/think>/s', '', $content));
    }

    /**
     * Full-resolution scans (e.g. 1920×2560) make the vision model time out;
     * 1600 px on the long side keeps text perfectly legible for OCR (~10× faster).
     *
     * @return array{0: string, 1: string} [bytes, mime]
     */
    private function downscaleImage(string $data, string $mime, int $maxSide = 1600): array
    {
        if (!extension_loaded('gd') || ($image = @imagecreatefromstring($data)) === false) {
            return [$data, $mime];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if (max($width, $height) <= $maxSide) {
            return [$data, $mime];
        }

        $scaled = imagescale($image, (int) round($width * $maxSide / max($width, $height)));
        ob_start();
        imagejpeg($scaled, null, 85);

        return [(string) ob_get_clean(), 'image/jpeg'];
    }

    /**
     * OpenAI-compatible multimodal call with an audio file.
     *
     * Uses the standard `input_audio` content part (raw base64 + format):
     * measured 8 s with an exact transcript, whereas `audio_url` data URLs took
     * 56 s and returned an empty message.
     */
    private function chatWithAudio(string $prompt, string $audioPath): string
    {
        $data = file_get_contents($audioPath);
        if ($data === false) {
            throw new \RuntimeException('Could not read audio: ' . $audioPath);
        }

        $body = [
            'model' => (string) ($this->config['chat_model'] ?? ''),
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'input_audio', 'input_audio' => [
                        'data' => base64_encode($data),
                        'format' => strtolower(pathinfo($audioPath, PATHINFO_EXTENSION)) ?: 'wav',
                    ]],
                ],
            ]],
            'temperature' => 0.0,
        ];

        // The model is non-deterministic: measured 1 assistant-style reply out
        // of 3 identical calls. Retry such replies; never index them.
        $attempts = max(1, (int) ($this->config['audio_attempts'] ?? 3));
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $payload = $this->request('/v1/chat/completions', $body);
            $content = trim((string) preg_replace('/<think>.*?<\/think>/s', '', (string) ($payload['choices'][0]['message']['content'] ?? '')));

            if ($content !== '' && !self::isNotATranscript($content)) {
                return $content;
            }
            error_log('[transcription] attempt ' . $attempt . '/' . $attempts . ' returned no transcript: ' . mb_substr($content, 0, 120));
            // A copyright refusal is deliberate and reproducible: retrying only
            // wastes minutes — hand over to the verbatim backends at once.
            if (preg_match('/copyright|droit\s+d.auteur/iu', $content) === 1) {
                break;
            }
        }

        if ($content === '') {
            throw new \RuntimeException('Empty transcription response from the model (unsupported audio format?).');
        }

        throw new \RuntimeException('Transcription refused by the model guardrails (likely copyrighted sung lyrics). Speech recordings are supported; for songs, configure the local whisper.cpp backend.');
    }

    /**
     * Detect assistant-style replies that are not a transcript (guardrail
     * refusals, "I don't have access to audio", offers of help) so they are
     * never indexed as if they were the recording's content.
     */
    public static function isNotATranscript(string $content): bool
    {
        return preg_match(
            '/\b(I\s+(can\'?t|cannot|can’t|am\s+unable\s+to|won\'?t)\s+(provide|transcribe|share|help\s+with|access|listen)'
            . '|I\s+(don\'?t|do\s+not|don’t)\s+have\s+(access|the\s+ability)'
            . '|(can|could)\s+only\s+work\s+with\s+text'
            . '|share\s+the\s+(text|transcript)'
            . '|I\'?m\s+(Muse|an?\s+AI)'
            . '|verbatim\s+transcript\s+of\s+(these|this|copyrighted)|copyrighted\s+(song|lyrics|material)'
            . '|je\s+ne\s+(peux|suis)\s+pas\s+(en\s+mesure\s+de\s+)?(fournir|transcrire|écouter|accéder)'
            . '|je\s+n\'?ai\s+pas\s+accès)/iu',
            $content,
        ) === 1;
    }

    /**
     * Perform a POST request to the Meta Model API and return the JSON body.
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function request(string $path, array $body, ?string $url = null, ?string $apiKey = null, ?int $timeout = null): array
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
            CURLOPT_TIMEOUT => $timeout ?? (int) ($this->config['timeout'] ?? 60),
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
