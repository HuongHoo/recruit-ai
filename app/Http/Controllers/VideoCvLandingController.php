<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Video CV" landing page: what the feature is and what employers see, with a way in.
 */
class VideoCvLandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $videoCv = $user?->isCandidate() ? $user->videoCv()->first() : null;

        return view('video-cv.landing', [
            // Guests are sent to login by the auth middleware and come back to the studio afterwards.
            'startUrl' => $user === null || $user->isCandidate() ? route('candidate.video-cv.edit') : null,
            'startLabel' => $videoCv ? 'Tiếp tục Video CV của bạn' : 'Tạo Video CV miễn phí',
            'config' => config('recruitment.video_cv'),
        ]);
    }
}
