<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\EducationRequest;
use App\Models\Education;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EducationController extends Controller
{
    public function create(): View
    {
        return view('candidate.education.form', ['education' => new Education]);
    }

    public function store(EducationRequest $request): RedirectResponse
    {
        $request->user()->educations()->create($request->validated());

        return redirect()->to(route('candidate.profile.edit').'#hoc-van')->with('status', 'Đã thêm học vấn.');
    }

    public function edit(Education $education): View
    {
        Gate::authorize('update', $education);

        return view('candidate.education.form', ['education' => $education]);
    }

    public function update(EducationRequest $request, Education $education): RedirectResponse
    {
        Gate::authorize('update', $education);

        $education->update($request->validated());

        return redirect()->to(route('candidate.profile.edit').'#hoc-van')->with('status', 'Đã cập nhật học vấn.');
    }

    public function destroy(Education $education): RedirectResponse
    {
        Gate::authorize('delete', $education);

        $education->delete();

        return redirect()->to(route('candidate.profile.edit').'#hoc-van')->with('status', 'Đã xóa học vấn.');
    }
}
