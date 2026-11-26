<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The mock interviewer's spoken lines as audio files. Each (voice, text) pair is generated once and
 * kept on the private disk, so shared lines ("Cảm ơn bạn.") and replays cost no extra AI calls.
 */
class InterviewVoice
{
    public function __construct(private GeminiClient $gemini) {}

    /**
     * Absolute path of the WAV clip for this line.
     *
     * @throws AiServiceException
     * @throws LockTimeoutException when another request is still generating the same clip
     */
    public function clip(string $text, string $voice): string
    {
        $disk = Storage::disk('local');
        $path = 'tts/'.sha1($voice."\n".$text).'.wav';

        if (! $disk->exists($path)) {
            // Two requests for the same line (prefetch + "play now") must not both call the AI.
            Cache::lock("tts:{$path}", 60)->block(45, function () use ($disk, $path, $text, $voice): void {
                if (! $disk->exists($path)) {
                    $disk->put($path, $this->gemini->speech($text, $voice));
                }
            });
        }

        return $disk->path($path);
    }
}
