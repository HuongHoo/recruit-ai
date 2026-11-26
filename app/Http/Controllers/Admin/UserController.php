<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $role = UserRole::tryFrom((string) $request->query('role'));
        $keyword = trim((string) $request->query('q'));

        return view('admin.users.index', [
            'users' => User::query()
                ->with(['company', 'socialAccounts'])
                ->when($role, fn ($query) => $query->where('role', $role))
                ->when($keyword !== '', fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'role' => $role,
        ]);
    }

    /**
     * Locks or unlocks an account. Admins cannot lock themselves or other admins.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Không thể khóa tài khoản Quản trị viên.']);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('status', $user->is_active ? "Đã mở khóa tài khoản {$user->email}." : "Đã khóa tài khoản {$user->email}.");
    }
}
