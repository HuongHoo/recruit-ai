@extends('layouts.app')
@section('title', 'Quản trị hệ thống')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="{{ $stat['border'] }} rounded-xl border border-l-4 border-slate-200 bg-white p-5">
                <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="font-semibold text-slate-900">Người dùng mới đăng ký</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th class="px-6 py-3">Tên</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Vai trò</th>
                        <th class="px-6 py-3">Trạng thái</th>
                        <th class="px-6 py-3">Ngày tạo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($latestUsers as $member)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium whitespace-nowrap text-slate-900">{{ $member->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $member->email }}</td>
                            <td class="px-6 py-3">
                                <span @class([
                                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-blue-100 text-blue-700' => $member->isCandidate(),
                                    'bg-emerald-100 text-emerald-700' => $member->isEmployer(),
                                    'bg-amber-100 text-amber-800' => $member->isAdmin(),
                                ])>{{ $member->role->label() }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @if ($member->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span>Hoạt động</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-red-700"><span class="size-2 rounded-full bg-red-500"></span>Bị khóa</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ $member->created_at?->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">Chưa có người dùng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
