<?php

namespace App\Http\Controllers\Candidate;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile();

        $checklist = [
            ['label' => 'Tạo tài khoản', 'done' => true, 'url' => null],
            ['label' => 'Thông tin cá nhân', 'done' => filled($profile->phone) && filled($profile->headline), 'url' => route('candidate.profile.edit').'#thong-tin'],
            ['label' => 'Mong muốn công việc', 'done' => filled($profile->desired_locations), 'url' => route('candidate.profile.edit').'#mong-muon'],
            ['label' => 'Kỹ năng', 'done' => filled($profile->skills), 'url' => route('candidate.profile.edit').'#mong-muon'],
            ['label' => 'Học vấn', 'done' => $user->educations()->exists(), 'url' => route('candidate.profile.edit').'#hoc-van'],
            ['label' => 'Kinh nghiệm làm việc', 'done' => $user->experiences()->exists(), 'url' => route('candidate.profile.edit').'#kinh-nghiem'],
            ['label' => 'Có ít nhất 1 CV', 'done' => $user->cvs()->exists(), 'url' => route('candidate.cvs.index')],
        ];

        $completed = collect($checklist)->where('done', true)->count();

        return view('candidate.dashboard', [
            'checklist' => $checklist,
            'completion' => (int) round($completed / count($checklist) * 100),
            'cvCount' => $user->cvs()->count(),
            'applicationCount' => $user->applications()->count(),
            'interviewCount' => $user->applications()->where('status', ApplicationStatus::Interview)->count(),
            'savedCount' => $user->savedJobs()->count(),
            'recentApplications' => $user->applications()->with('jobPost')->latest()->limit(5)->get(),
        ]);
    }
}
