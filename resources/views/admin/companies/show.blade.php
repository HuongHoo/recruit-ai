@extends('layouts.app')
@section('title', 'Hồ sơ công ty')

@php /** @var \App\Models\Company $company */ @endphp

@section('content')
    <a href="{{ route('admin.companies.index') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-800">&larr; Danh sách công ty</a>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 lg:col-span-2">
            <div class="flex items-start gap-4">
                <img src="{{ $company->logoUrl() }}" alt="" class="size-20 rounded-xl border border-slate-200 object-cover">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $company->name }}</h2>
                    <span class="{{ $company->status->badgeClasses() }} mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $company->status->label() }}</span>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Mô hình hoạt động</dt><dd class="font-medium text-slate-900">{{ $company->businessModelLabel() }}</dd></div>
                <div><dt class="text-slate-500">Quy mô</dt><dd class="font-medium text-slate-900">{{ $company->sizeLabel() ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Ngành nghề</dt><dd class="font-medium text-slate-900">{{ $company->industry ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Website</dt><dd><a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer nofollow" class="font-medium text-blue-600 hover:underline">{{ $company->website }}</a></dd></div>
                <div><dt class="text-slate-500">Điện thoại</dt><dd class="font-medium text-slate-900">{{ $company->phone }}</dd></div>
                <div><dt class="text-slate-500">Người đăng ký</dt><dd class="font-medium text-slate-900">{{ $company->owner->name }} · {{ $company->owner->email }}</dd></div>
            </dl>

            <div class="mt-6">
                <p class="text-sm text-slate-500">Hồ sơ công ty</p>
                <p class="mt-1 text-sm whitespace-pre-line text-slate-800">{{ $company->description ?: 'Chưa có mô tả.' }}</p>
            </div>

            @if ($company->reviewed_at)
                <p class="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-500">
                    Duyệt gần nhất: {{ $company->reviewed_at->format('d/m/Y H:i') }} bởi {{ $company->reviewer?->name ?? '—' }}
                    @if ($company->rejection_reason) · Lý do từ chối: {{ $company->rejection_reason }} @endif
                </p>
            @endif
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
                <p class="font-semibold text-slate-900">Cần kiểm tra</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Website hoạt động và đúng tên công ty</li>
                    <li>Email đăng ký nên cùng tên miền với website</li>
                    <li>Logo và mô tả phù hợp, không giả mạo</li>
                </ul>
            </div>

            @unless ($company->isVerified())
                <form method="POST" action="{{ route('admin.companies.approve', $company) }}">
                    @csrf @method('PATCH')
                    <button class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white hover:bg-emerald-700">Xác thực công ty</button>
                </form>
            @endunless

            <form method="POST" action="{{ route('admin.companies.reject', $company) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
                @csrf @method('PATCH')
                <label for="rejection_reason" class="form-label">{{ $company->isVerified() ? 'Thu hồi xác thực' : 'Từ chối' }} — lý do gửi cho nhà tuyển dụng</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required minlength="10" class="form-input"
                          placeholder="VD: Website không truy cập được / không khớp tên công ty.">{{ old('rejection_reason') }}</textarea>
                @error('rejection_reason') <p class="form-error">{{ $message }}</p> @enderror
                <button class="mt-3 w-full rounded-lg border border-red-300 px-4 py-2.5 font-semibold text-red-700 hover:bg-red-50">
                    {{ $company->isVerified() ? 'Thu hồi xác thực' : 'Từ chối' }}
                </button>
            </form>
        </aside>
    </div>
@endsection
