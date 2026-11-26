@extends('layouts.app')
@section('title', 'Cài đặt công ty')
@section('subtitle', 'Cập nhật và cấu hình thông tin công ty')
@section('icon', 'cog')

@php
    $company = auth()->user()->company;

    /** [title, description, icon path, route name|null] — null = not built yet. */
    $cards = [
        ['Thông tin công ty', 'Quản lý hồ sơ công ty, logo, điều khoản sử dụng và thông tin liên hệ.', 'M4 21V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17m-12 0h16m-4 0V9h3a1 1 0 0 1 1 1v11M8 7h4M8 11h4M8 15h4', 'employer.company.edit'],
        ['Thống kê sử dụng', 'Theo dõi chi tiết mức độ sử dụng các tính năng trong gói của công ty.', 'M5 20V12m5 8V8m5 12v-6m5 6V4', null],
        ['Thành viên nhóm', 'Quản lý thành viên nhóm và mời người dùng mới vào công ty.', 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m18 0v-1a4 4 0 0 0-3-3.9M14 4.1a4 4 0 0 1 0 7.8M14 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z', null],
        ['Quản lý KPI', 'Thiết lập chỉ tiêu và theo dõi thống kê hiệu suất của nhân viên.', 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-5a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0-3a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z', null],
        ['Danh mục', 'Quản lý danh mục riêng của công ty.', 'M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9Zm5-4h.01', null],
        ['Nguồn ứng viên', 'Quản lý các nguồn ứng viên đến từ đâu (Facebook, Website, LinkedIn, v.v.)', 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m18 0v-1a4 4 0 0 0-3-3.9M14 4.1a4 4 0 0 1 0 7.8M14 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z', null],
        ['Mẫu email', 'Quản lý mẫu email cho công ty của bạn.', 'M4 6h16v12H4V6Zm0 0 8 7 8-7', null],
        ['Cấu hình email gửi', 'Xác thực địa chỉ email và gửi email thử nghiệm để đảm bảo hệ thống hoạt động đúng.', 'M4 6h16v12H4V6Zm0 0 8 7 8-7', null],
        ['Cài đặt lịch phỏng vấn', 'Cấu hình thông báo email và thời gian gửi thông báo trước lịch phỏng vấn.', 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z', null],
        ['Thiết lập quy trình tuyển dụng', 'Quản lý các trạng thái, thứ tự hiển thị và mẫu email khi chuyển trạng thái.', 'M4 5h4v14H4V5Zm6 0h4v14h-4V5Zm6 0h4v14h-4V5Z', null],
        ['Điều khoản thỏa thuận', 'Quản lý nội dung điều khoản hiển thị cho ứng viên.', 'M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm2 9h6m-6 4h6', null],
    ];
@endphp

@section('content')
    {{-- Company summary --}}
    <section class="flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4">
        @if ($company?->logo_path)
            <img src="{{ $company->logoUrl() }}" alt="" class="size-14 rounded-xl border border-slate-200 object-cover">
        @else
            <span class="grid size-14 place-items-center rounded-xl bg-teal-600 text-xl font-bold text-white">{{ mb_strtoupper(mb_substr($company->name ?? '?', 0, 1)) }}</span>
        @endif
        <div class="min-w-0 flex-1">
            <p class="truncate text-lg font-bold text-slate-900">{{ $company->name ?? 'Chưa có hồ sơ công ty' }}</p>
            <p class="truncate text-sm text-slate-500">
                @if ($company)
                    {{ collect([$company->industry, $company->sizeLabel(), $company->website])->filter()->implode(' · ') ?: 'Bổ sung ngành nghề, quy mô và website để ứng viên hiểu hơn về công ty.' }}
                @else
                    Tạo hồ sơ công ty để được xác thực và bắt đầu đăng tin tuyển dụng.
                @endif
            </p>
        </div>
        @if ($company)
            <span class="{{ $company->status->badgeClasses() }} rounded-full px-3 py-1 text-xs font-semibold">{{ $company->status->label() }}</span>
        @endif
        <a href="{{ route('employer.company.edit') }}" class="rounded-lg border border-teal-600 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">{{ $company ? 'Chỉnh sửa' : 'Tạo hồ sơ' }}</a>
    </section>

    {{-- Settings cards --}}
    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as [$title, $description, $icon, $route])
            @php $tag = $route ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($route) href="{{ route($route) }}" @else title="Chức năng sắp ra mắt" @endif
                @class(['group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-6 transition',
                    'hover:border-teal-400 hover:shadow-md hover:shadow-teal-900/5' => $route,
                    'cursor-not-allowed' => ! $route])>
                <span @class(['grid size-11 shrink-0 place-items-center rounded-xl border border-slate-200 text-slate-600', 'group-hover:border-teal-300 group-hover:bg-teal-50 group-hover:text-teal-700' => $route])>
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2">
                        <span @class(['text-[17px] font-bold', 'text-slate-900 group-hover:text-teal-700' => $route, 'text-slate-500' => ! $route])>{{ $title }}</span>
                        @unless ($route) <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">Sắp có</span> @endunless
                    </span>
                    <span class="mt-1 block text-[13px] leading-relaxed text-slate-500">{{ $description }}</span>
                </span>
                <svg @class(['size-4 shrink-0', 'text-slate-400 group-hover:translate-x-0.5 group-hover:text-teal-600' => $route, 'text-slate-300' => ! $route]) fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
            </{{ $tag }}>
        @endforeach
    </div>
@endsection
