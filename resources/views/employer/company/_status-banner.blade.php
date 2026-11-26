{{-- Verification status of the employer's company. Hidden once the company is verified. --}}
@php /** @var \App\Models\Company|null $company */ @endphp

@if ($company === null)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
        <p class="font-semibold">Bạn chưa có hồ sơ công ty</p>
        <p class="mt-1">Tạo hồ sơ công ty để Quản trị viên xác thực trước khi đăng tin tuyển dụng.
            <a href="{{ route('employer.company.edit') }}" class="font-semibold underline">Tạo hồ sơ ngay</a></p>
    </div>
@elseif ($company->status === \App\Enums\CompanyStatus::Pending)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
        <p class="font-semibold">Công ty {{ $company->name }} đang chờ xác thực</p>
        <p class="mt-1">Quản trị viên sẽ kiểm tra thông tin trong vòng 1 ngày làm việc. Bạn sẽ nhận email khi có kết quả và có thể đăng tin ngay sau khi được xác thực.</p>
    </div>
@elseif ($company->status === \App\Enums\CompanyStatus::Rejected)
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900">
        <p class="font-semibold">Hồ sơ công ty chưa được xác thực</p>
        <p class="mt-1">Lý do: {{ $company->rejection_reason }}</p>
        <a href="{{ route('employer.company.edit') }}" class="mt-2 inline-block font-semibold underline">Cập nhật và gửi duyệt lại</a>
    </div>
@endif
