<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userCounts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'Tổng người dùng', 'value' => (int) $userCounts->sum(), 'border' => 'border-l-slate-700'],
                ['label' => 'Ứng viên', 'value' => (int) ($userCounts[UserRole::Candidate->value] ?? 0), 'border' => 'border-l-blue-500'],
                ['label' => 'Nhà tuyển dụng', 'value' => (int) ($userCounts[UserRole::Employer->value] ?? 0), 'border' => 'border-l-emerald-500'],
                ['label' => 'Công ty chờ xác thực', 'value' => Company::query()->where('status', CompanyStatus::Pending)->count(), 'border' => 'border-l-amber-500'],
            ],
            'latestUsers' => User::query()->latest()->limit(6)->get(),
        ]);
    }
}
