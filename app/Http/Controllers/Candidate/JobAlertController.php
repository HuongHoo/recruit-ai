<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Candidate\JobAlertRequest;
use App\Models\JobAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobAlertController extends Controller
{
    public function index(Request $request): View
    {
        return view('candidate.job-alerts.index', [
            'alerts' => $request->user()->jobAlerts()->latest()->get(),
            'maxAlerts' => config('recruitment.max_job_alerts'),
        ]);
    }

    public function store(JobAlertRequest $request): RedirectResponse
    {
        $user = $request->user();
        $max = config('recruitment.max_job_alerts');

        if ($user->jobAlerts()->count() >= $max) {
            throw ValidationException::withMessages(['keyword' => "Bạn chỉ có thể tạo tối đa {$max} thông báo việc làm."]);
        }

        $user->jobAlerts()->create($request->validated());

        return redirect()->route('candidate.job-alerts.index')
            ->with('status', 'Đã đăng ký nhận thông báo. Bạn sẽ nhận email khi có việc làm mới phù hợp.');
    }

    /**
     * Toggles the alert on/off.
     */
    public function update(JobAlert $jobAlert): RedirectResponse
    {
        Gate::authorize('update', $jobAlert);

        $jobAlert->update(['is_active' => ! $jobAlert->is_active]);

        return back()->with('status', $jobAlert->is_active ? 'Đã bật thông báo.' : 'Đã tạm dừng thông báo.');
    }

    public function destroy(JobAlert $jobAlert): RedirectResponse
    {
        Gate::authorize('delete', $jobAlert);

        $jobAlert->delete();

        return back()->with('status', 'Đã xóa thông báo việc làm.');
    }
}
