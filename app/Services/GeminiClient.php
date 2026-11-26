<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Google Gemini's generateContent endpoint that always asks for JSON output.
 *
 * Gemini models are often temporarily overloaded (503) or slow; when that happens the request
 * moves on to the next model in services.gemini.fallback_models instead of failing.
 */
class GeminiClient
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /** Statuses that mean "this model is busy right now", worth trying another model. */
    private const OVERLOADED = [500, 502, 503, 504];

    /** Model that produced the last successful response. */
    private ?string $usedModel = null;

    /**
     * @param  array<int, array<string, mixed>>  $parts  user message parts (text and/or inline_data)
     * @param  array<string, mixed>  $schema  Gemini responseSchema
     * @param  int|null  $timeout  seconds per model attempt; defaults to services.gemini.timeout
     * @return array<string, mixed>
     *
     * @throws AiServiceException
     */
    public function generateJson(string $instructions, array $parts, array $schema, float $temperature = 0.2, ?int $timeout = null): array
    {
        $key = config('services.gemini.key');

        if (blank($key)) {
            throw new AiServiceException('Chức năng AI chưa được cấu hình (thiếu GEMINI_API_KEY).');
        }

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $instructions]]],
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => [
                'temperature' => $temperature,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];

        $response = null;

        foreach ($this->models() as $model) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->timeout($timeout ?? config('services.gemini.timeout'))
                    ->post(sprintf(self::ENDPOINT, $model), $payload);
            } catch (ConnectionException $exception) {
                Log::warning('Gemini request timed out', ['model' => $model, 'error' => $exception->getMessage()]);
                $response = null;

                continue;
            }

            if (! in_array($response->status(), self::OVERLOADED, true)) {
                $this->usedModel = $model;

                break;
            }

            Log::warning('Gemini model overloaded, trying next', ['model' => $model, 'status' => $response->status()]);
        }

        return $this->decode($response);
    }

    /**
     * Reads the text aloud with a Gemini TTS voice. The language is detected from the text, so the
     * text is sent as-is (style instructions in the prompt would be read aloud too).
     *
     * @return string WAV file contents
     *
     * @throws AiServiceException
     */
    public function speech(string $text, string $voice): string
    {
        $key = config('services.gemini.key');

        if (blank($key)) {
            throw new AiServiceException('Chức năng AI chưa được cấu hình (thiếu GEMINI_API_KEY).');
        }

        $payload = [
            'contents' => [['parts' => [['text' => $text]]]],
            'generationConfig' => [
                'responseModalities' => ['AUDIO'],
                'speechConfig' => ['voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => $voice]]],
            ],
        ];

        $models = array_values(array_filter(array_map('trim', explode(',', (string) config('services.gemini.tts_models')))));

        foreach ($models as $model) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $key])
                    ->timeout(config('services.gemini.tts_timeout'))
                    ->post(sprintf(self::ENDPOINT, $model), $payload);
            } catch (ConnectionException $exception) {
                Log::warning('Gemini TTS request timed out', ['model' => $model, 'error' => $exception->getMessage()]);

                continue;
            }

            $audio = $response->successful() ? $this->wav($response) : null;

            if ($audio !== null) {
                return $audio;
            }

            // Rate limited (429), overloaded, or the model refused this text: another model may still work.
            Log::warning('Gemini TTS model failed, trying next', ['model' => $model, 'status' => $response->status()]);
        }

        throw new AiServiceException('Giọng đọc AI đang bận. Vui lòng thử lại sau ít phút.');
    }

    /**
     * The model to record with a result: the one that answered last, or the configured primary model.
     */
    public function model(): string
    {
        return $this->usedModel ?? (string) config('services.gemini.model');
    }

    /**
     * @return array<int, string> primary model first, then fallbacks, without duplicates
     */
    private function models(): array
    {
        $fallbacks = array_map('trim', explode(',', (string) config('services.gemini.fallback_models')));

        return array_values(array_unique(array_filter([(string) config('services.gemini.model'), ...$fallbacks])));
    }

    /**
     * Audio from a TTS response as a WAV file. Newer models already send WAV; older ones send raw
     * 16-bit mono PCM ("audio/L16;codec=pcm;rate=24000") that needs a WAV header.
     */
    private function wav(Response $response): ?string
    {
        $part = $response->json('candidates.0.content.parts.0.inlineData');
        $bytes = is_array($part) ? base64_decode((string) ($part['data'] ?? ''), true) : false;

        if ($bytes === false || $bytes === '') {
            return null;
        }

        $mime = strtolower((string) ($part['mimeType'] ?? ''));

        if (str_contains($mime, 'wav')) {
            return $bytes;
        }

        $rate = preg_match('/rate=(\d+)/', $mime, $match) ? (int) $match[1] : 24000;
        $length = strlen($bytes);

        return 'RIFF'.pack('V', 36 + $length).'WAVE'
            .'fmt '.pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
            .'data'.pack('V', $length).$bytes;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AiServiceException
     */
    private function decode(?Response $response): array
    {
        if ($response === null) {
            throw new AiServiceException('Không kết nối được tới dịch vụ AI. Vui lòng thử lại sau.');
        }

        if ($response->status() === 429) {
            throw new AiServiceException('Đã hết lượt dùng AI miễn phí trong lúc này. Vui lòng thử lại sau ít phút.');
        }

        if ($response->failed()) {
            Log::warning('Gemini request failed', ['status' => $response->status(), 'body' => $response->body()]);

            throw new AiServiceException(in_array($response->status(), self::OVERLOADED, true)
                ? 'Dịch vụ AI đang quá tải. Vui lòng thử lại sau ít phút.'
                : 'Dịch vụ AI đang gặp lỗi. Vui lòng thử lại sau.');
        }

        $data = json_decode((string) $response->json('candidates.0.content.parts.0.text'), true);

        if (! is_array($data)) {
            Log::warning('Gemini returned non-JSON payload', ['body' => $response->body()]);

            throw new AiServiceException('AI trả về kết quả không hợp lệ. Vui lòng thử lại.');
        }

        return $data;
    }
}
