<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a file from the private "local" disk. BinaryFileResponse honours Range requests,
 * so video players can seek without downloading the whole clip.
 */
class PrivateMedia
{
    public static function stream(?string $path, ?string $mimeType = null): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return response()->file($disk->path($path), array_filter([
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }
}
