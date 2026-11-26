@extends('layouts.app')
@section('title', 'Xác thực công ty')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <nav class="flex flex-wrap gap-2">
            @foreach (\App\Enums\CompanyStatus::cases() as $case)
                <a href="{{ route('admin.companies.index', ['status' => $case->value]) }}"
                   @class([
                       'rounded-full px-4 py-1.5 text-sm font-medium',
                       'bg-slate-900 text-white' => $status === $case,
                       'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $status !== $case,
                   ])>
                    {{ $case->label() }} <span class="opacity-70">({{ $counts[$case->value] ?? 0 }})</span>
                </a>
            @endforeach
        </nav>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="status" value="{{ $status?->value }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Tên công ty, website…" class="form-input w-64">
            <button class="btn-primary">Tìm</button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wider text-slate-500 uppercase">
                <tr>
                    <th class="px-6 py-3">Công ty</th>
                    <th class="px-6 py-3">Người đăng ký</th>
                    <th class="px-6 py-3">Mô hình</th>
                    <th class="px-6 py-3">Trạng thái</th>
                    <th class="px-6 py-3">Ngày gửi</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($companies as $company)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $company->logoUrl() }}" alt="" class="size-10 rounded-lg border border-slate-200 object-cover">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $company->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $company->website }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-slate-600">{{ $company->owner->name }}<br><span class="text-xs">{{ $company->owner->email }}</span></td>
                        <td class="px-6 py-3 text-slate-600">{{ $company->business_model === 'agency' ? 'Agency' : 'Nội bộ' }}</td>
                        <td class="px-6 py-3"><span class="{{ $company->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $company->status->label() }}</span></td>
                        <td class="px-6 py-3 whitespace-nowrap text-slate-500">{{ $company->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-3 text-right"><a href="{{ route('admin.companies.show', $company) }}" class="font-semibold text-blue-600 hover:text-blue-700">Xem &amp; duyệt</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-500">Không có công ty nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $companies->links() }}</div>
@endsection
