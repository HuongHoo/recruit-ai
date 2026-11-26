@extends('layouts.app')
@section('title', 'Người dùng')

@section('content')
    <form method="GET" class="mb-6 flex flex-wrap gap-2">
        <select name="role" class="form-input w-48" onchange="this.form.submit()">
            <option value="">Tất cả vai trò</option>
            @foreach (\App\Enums\UserRole::cases() as $case)
                <option value="{{ $case->value }}" @selected($role === $case)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Tên hoặc email…" class="form-input w-64">
        <button class="btn-primary">Tìm</button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">
                <tr>
                    <th class="px-6 py-3">Tên</th>
                    <th class="px-6 py-3">Vai trò</th>
                    <th class="px-6 py-3">Công ty</th>
                    <th class="px-6 py-3">Đăng nhập</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3">Ngày tạo</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $member)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3"><p class="font-medium text-slate-900">{{ $member->name }}</p><p class="text-xs text-slate-500">{{ $member->email }}</p></td>
                        <td class="px-6 py-3 text-slate-600">{{ $member->role->label() }}</td>
                        <td class="px-6 py-3">
                            @if ($member->company)
                                <a href="{{ route('admin.companies.show', $member->company) }}" class="text-slate-700 hover:underline">{{ $member->company->name }}</a>
                                <span class="{{ $member->company->status->badgeClasses() }} ml-1 rounded-full px-2 py-0.5 text-[11px] font-semibold">{{ $member->company->status->label() }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-slate-600">{{ $member->socialAccounts->map(fn ($account) => $account->provider->label())->join(', ') ?: 'Email' }}</td>
                        <td class="px-6 py-3">
                            @if ($member->is_active)
                                <span class="inline-flex items-center gap-1.5 text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span>Hoạt động</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-red-700"><span class="size-2 rounded-full bg-red-500"></span>Bị khóa</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ $member->created_at?->format('d/m/Y') }}</td>
                        <td class="px-6 py-3 text-right">
                            @unless ($member->isAdmin())
                                <form method="POST" action="{{ route('admin.users.toggle-active', $member) }}"
                                      onsubmit="return confirm('{{ $member->is_active ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?' }}')">
                                    @csrf @method('PATCH')
                                    <button @class(['font-semibold', 'text-red-600 hover:text-red-700' => $member->is_active, 'text-emerald-600 hover:text-emerald-700' => ! $member->is_active])>
                                        {{ $member->is_active ? 'Khóa' : 'Mở khóa' }}
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-500">Không có người dùng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
@endsection
