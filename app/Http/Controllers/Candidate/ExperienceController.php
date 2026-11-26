<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\ExperienceRequest;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ExperienceController extends Controller
{
    public function create(): View
    {
        return view('candidate.experience.form', ['experience' => new Experience]);
    }

    public function store(ExperienceRequest $request): RedirectResponse
    {
        $request->user()->experiences()->create($request->validated());

        return redirect()->to(route('candidate.profile.edit').'#kinh-nghiem')->with('status', 'Đã thêm kinh nghiệm làm việc.');
    }

    public function edit(Experience $experience): View
    {
        Gate::authorize('update', $experience);

        return view('candidate.experience.form', ['experience' => $experience]);
    }

    public function update(ExperienceRequest $request, Experience $experience): RedirectResponse
    {
        Gate::authorize('update', $experience);

        $experience->update($request->validated());

        return redirect()->to(route('candidate.profile.edit').'#kinh-nghiem')->with('status', 'Đã cập nhật kinh nghiệm làm việc.');
    }

    public function destroy(Experience $experience): RedirectResponse
    {
        Gate::authorize('delete', $experience);

        $experience->delete();

        return redirect()->to(route('candidate.profile.edit').'#kinh-nghiem')->with('status', 'Đã xóa kinh nghiệm làm việc.');
    }
}
