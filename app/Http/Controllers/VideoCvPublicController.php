<?php

namespace App\Http\Controllers;

use App\Models\VideoCv;
use App\Models\VideoCvAnswer;
use App\Support\PrivateMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The page behind a Video CV share link. Anyone with the link can view it until the candidate
 * stops sharing or renews the link; there is no listing or search of Video CVs.
 */
class VideoCvPublicController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $videoCv = $this->find($token)->load(['answers', 'cv', 'user']);
        $isOwner = $request->user()?->id === $videoCv->user_id;

        if (! $isOwner) {
            $videoCv->increment('view_count');
        }

        return response()
            ->view('video-cv.public', [
                'videoCv' => $videoCv,
                'answers' => $videoCv->recordedAnswers(),
                'photoUrl' => $videoCv->photo_path ? route('video-cv.public.photo', $token) : null,
                'answerUrl' => fn (VideoCvAnswer $answer) => route('video-cv.public.answer', [$token, $answer]),
                'cvUrl' => $videoCv->cv ? route('video-cv.public.cv', $token) : null,
                'isPreview' => false,
                'isOwner' => $isOwner,
            ])
            // Share links are private by nature: keep them out of search engines.
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function answer(string $token, VideoCvAnswer $answer): BinaryFileResponse
    {
        abort_unless($answer->video_cv_id === $this->find($token)->id, 404);

        return PrivateMedia::stream($answer->file_path, $answer->mime_type);
    }

    public function photo(string $token): BinaryFileResponse
    {
        return PrivateMedia::stream($this->find($token)->photo_path);
    }

    public function cv(string $token): View|BinaryFileResponse
    {
        $cv = $this->find($token)->cv;
        abort_if($cv === null, 404);

        if (! $cv->isOnline()) {
            return PrivateMedia::stream($cv->file_path, $cv->mime_type)
                ->setContentDisposition($cv->isPdf() ? 'inline' : 'attachment', $cv->original_name, 'cv.'.pathinfo($cv->original_name, PATHINFO_EXTENSION));
        }

        $user = $cv->user;

        return view('candidate.cvs.show', [
            'cv' => $cv,
            'user' => $user,
            'profile' => $user->profile(),
            'educations' => $user->educations()->get(),
            'experiences' => $user->experiences()->get(),
            'backUrl' => route('video-cv.public', $token),
        ]);
    }

    private function find(string $token): VideoCv
    {
        return VideoCv::query()->where('share_token', $token)->firstOrFail();
    }
}
