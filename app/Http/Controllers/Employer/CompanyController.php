<?php

namespace App\Http\Controllers\Employer;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employer\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function edit(Request $request): View
    {
        return view('employer.company.edit', [
            'company' => $request->user()->company ?? new Company,
            'businessModels' => config('recruitment.employer.business_models'),
            'companySizes' => config('recruitment.employer.company_sizes'),
            'industries' => config('recruitment.job_categories'),
        ]);
    }

    /**
     * Saves the company. A rejected company goes back to the review queue; changing the legal identity
     * (name / website) of a verified company also requires a new review, like real job boards do.
     */
    public function update(CompanyRequest $request): RedirectResponse
    {
        $user = $request->user();
        $company = $user->company ?? $user->company()->make();
        $data = Arr::except($request->validated(), ['logo']);
        $data['contact_email'] = $data['contact_email'] ?? $request->user()->email;

        if ($request->hasFile('logo')) {
            $oldLogo = $company->logo_path;
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        $company->fill($data);
        $needsReview = $company->status === CompanyStatus::Rejected
            || ($company->status === CompanyStatus::Verified && $company->isDirty(['name', 'website']));
        $company->save();

        if (isset($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        if ($needsReview) {
            $company->resubmit();

            return redirect()->route('employer.company.edit')
                ->with('status', 'Đã lưu và gửi lại hồ sơ công ty cho Quản trị viên xác thực.');
        }

        return redirect()->route('employer.company.edit')->with('status', 'Đã cập nhật hồ sơ công ty.');
    }
}
