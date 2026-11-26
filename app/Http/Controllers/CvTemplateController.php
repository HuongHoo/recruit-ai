<?php

namespace App\Http\Controllers;

use App\Support\CvSampleData;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public CV template gallery ("Mẫu CV"). Previews are rendered with sample data, never a real user's.
 */
class CvTemplateController extends Controller
{
    public function index(): View
    {
        return view('cv-templates.index', [
            'templates' => config('recruitment.cv.templates'),
            ...CvSampleData::make(),
        ]);
    }

    public function show(Request $request, string $template): View
    {
        $templates = config('recruitment.cv.templates');
        abort_unless(isset($templates[$template]), 404);

        $user = $request->user();

        return view('cv-templates.show', [
            'key' => $template,
            'template' => $templates[$template],
            'otherTemplates' => collect($templates)->except($template),
            'savedCvs' => $user?->isCandidate() ? $user->cvs()->latest()->get() : collect(),
            ...CvSampleData::make($template),
        ]);
    }
}
