<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Notifications\CompanyReviewed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(CompanyStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $status = CompanyStatus::tryFrom((string) $request->query('status', CompanyStatus::Pending->value));
        $keyword = trim((string) $request->query('q'));

        return view('admin.companies.index', [
            'companies' => Company::query()
                ->with('owner')
                ->when($status, fn ($query) => $query->where('status', $status))
                ->when($keyword !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('website', 'like', "%{$keyword}%")))
                ->oldest()
                ->paginate(15)
                ->withQueryString(),
            'status' => $status,
            'counts' => Company::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Company $company): View
    {
        return view('admin.companies.show', ['company' => $company->load(['owner', 'reviewer'])]);
    }

    public function approve(Request $request, Company $company): RedirectResponse
    {
        $company->approve($request->user());
        $company->owner->notify(new CompanyReviewed($company));

        return redirect()->route('admin.companies.index')->with('status', "Đã xác thực công ty {$company->name}.");
    }

    public function reject(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate(
            ['rejection_reason' => ['required', 'string', 'min:10', 'max:1000']],
            [],
            ['rejection_reason' => 'lý do từ chối'],
        );

        $company->reject($request->user(), $data['rejection_reason']);
        $company->owner->notify(new CompanyReviewed($company));

        return redirect()->route('admin.companies.index')->with('status', "Đã từ chối công ty {$company->name}.");
    }
}
