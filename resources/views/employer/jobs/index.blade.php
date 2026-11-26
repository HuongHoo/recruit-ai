@extends('layouts.app')
@section('title', 'Quản lý việc làm')
@section('subtitle', 'Tạo và quản lý hồ sơ việc làm, và sàng lọc ứng viên phù hợp.')
@section('icon', 'briefcase')

@section('content')
    @if ($jobs->isNotEmpty())
        <div class="mb-5 flex justify-end">
            <a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">+ Tạo tin tuyển dụng</a>
        </div>
    @endif

    @if ($jobs->isEmpty())
        <div class="flex flex-col items-center gap-8 rounded-2xl border border-slate-200 bg-white px-6 py-8 sm:flex-row sm:px-10">
            <svg class="w-44 shrink-0 text-slate-400" viewBox="0 0 180 110" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="8" y="22" width="34" height="46" rx="4" transform="rotate(-10 25 45)"/>
                <path d="M16 36h6m4 0h8M17 46h6m4 0h8M18 56h6m4 0h8" transform="rotate(-10 25 45)"/>
                <rect x="52" y="58" width="58" height="38" rx="5"/><path d="M72 58v-5h18v5M70 82h22"/>
                <path d="M60 58V20h34l10 10v28" fill="#fff"/><path d="M94 20v10h10M68 32h18M68 40h26M68 48h20"/>
                <circle cx="76" cy="28" r="0" />
                <path d="M110 74h10"/><rect x="120" y="68" width="12" height="12" rx="3" stroke="#0d9488"/><circle cx="126" cy="74" r="2" fill="#0d9488" stroke="#0d9488"/>
                <path d="M132 74h8"/><rect x="140" y="68" width="12" height="12" rx="3"/><path d="M152 74h8"/><circle cx="164" cy="74" r="4"/>
                <path d="M84 8v6M74 11l3 4M94 11l-3 4"/>
            </svg>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Chưa có tin tuyển dụng</h2>
                <p class="mt-2 text-sm text-slate-500">Tạo tin đầu tiên để giới thiệu công việc và nhận hồ sơ ứng viên. Sau khi lưu, bạn có thể kiểm tra nội dung và chỉnh sửa tin bất cứ lúc nào.</p>
                <a href="{{ route('employer.jobs.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg border border-teal-600 px-5 py-2.5 text-sm font-semibold text-teal-700 hover:bg-teal-50">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14m-7-7h14"/></svg>
                    Tạo tin tuyển dụng
                </a>
                <p class="mt-3 text-xs text-slate-500">Cần hồ sơ công ty đã được xác thực để đăng tin.</p>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3">Vị trí</th>
                        <th class="px-5 py-3">Trạng thái</th>
                        <th class="px-5 py-3">Hạn nộp</th>
                        <th class="px-5 py-3">Hồ sơ</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($jobs as $job)
                        <tr>
                            <td class="px-5 py-4">
                                <a href="{{ route('employer.jobs.show', $job) }}" class="font-semibold text-slate-900 hover:text-teal-700">{{ $job->title }}</a>
                                <p class="text-xs text-slate-500">{{ $job->location }} · {{ $job->salaryLabel() }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-teal-100 text-teal-700' => $job->isVisible(),
                                    'bg-slate-100 text-slate-600' => ! $job->isVisible(),
                                ])>{{ $job->isVisible() ? 'Đang hiển thị' : ($job->status->value === 'closed' ? 'Đã đóng' : 'Hết hạn') }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $job->deadline?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route('employer.jobs.show', $job) }}" class="font-semibold text-slate-900 hover:text-teal-700">{{ $job->applications_count }}</a>
                                @if ($job->new_applications_count)
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ $job->new_applications_count }} mới</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('jobs.show', $job) }}" target="_blank" class="rounded-lg px-2.5 py-1.5 font-medium text-slate-600 hover:bg-slate-100">Xem</a>
                                <a href="{{ route('employer.jobs.edit', $job) }}" class="rounded-lg px-2.5 py-1.5 font-medium text-slate-600 hover:bg-slate-100">Sửa</a>
                                <form method="POST" action="{{ route('employer.jobs.destroy', $job) }}" class="inline" onsubmit="return confirm(@js('Xóa tin "'.$job->title.'" và toàn bộ hồ sơ ứng tuyển?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg px-2.5 py-1.5 font-medium text-red-600 hover:bg-red-50">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $jobs->links() }}</div>
    @endif
@endsection
