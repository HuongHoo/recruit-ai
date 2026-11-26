<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Support\CvSampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Tạo CV" landing page: explains the CV builder and sends the visitor into it.
 */
class CvBuilderLandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('cv-builder.landing', [
            'templates' => config('recruitment.cv.templates'),
            // Guests are sent to login by the auth middleware and come back to the builder afterwards.
            'startUrl' => $user === null || $user->isCandidate() ? route('candidate.cvs.create') : route('cv-templates.index'),
            'companies' => Company::query()->where('status', CompanyStatus::Verified)->latest('verified_at')->limit(12)->get(['id', 'name', 'logo_path']),
            'companyCount' => Company::query()->where('status', CompanyStatus::Verified)->count(),
            ...CvSampleData::make(),
        ]);
    }
}
