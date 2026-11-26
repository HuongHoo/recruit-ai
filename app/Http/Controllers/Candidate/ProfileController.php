<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('candidate.profile.edit', [
            'user' => $user,
            'profile' => $user->profile(),
            'educations' => $user->educations()->get(),
            'experiences' => $user->experiences()->get(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update(['name' => $data['name']]);
        $user->profile()->update(Arr::except($data, ['name']));

        return redirect()->route('candidate.profile.edit')->with('status', 'Đã cập nhật hồ sơ cá nhân.');
    }
}
