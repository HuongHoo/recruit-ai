@extends('layouts.app')
@section('title', 'Việc đã ứng tuyển')

@section('content')
    @php use App\Enums\ApplicationStatus; @endphp

    {{-- Status filter tabs --}}
    <nav class="mb-6 flex flex-wrap gap-2 text-sm font-medium">
        <a href="{{ route('candidate.applications.index') }}" @class([
            'rounded-full px-4 py-1.5',
            'bg-blue-600 text-white' => ! $currentStatus,
            'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' => $currentStatus,
        ])>Tất cả ({{ $statusCounts->sum() }})</a>
        @foreach (ApplicationStatus::cases() as $status)
            <a href="{{ route('candidate.applications.index', ['status' => $status->value]) }}" @class([
                'rounded-full px-4 py-1.5',
                'bg-blue-600 text-white' => $currentStatus === $status->value,
                'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' => $currentStatus !== $status->value,
            ])>{{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})</a>
        @endforeach
    </nav>

    @if ($applications->isEmpty())
        <div class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
            <p class="font-semibold text-slate-800">Chưa có đơn ứng tuyển nào</p>
            <p class="mt-1 text-sm text-slate-500">Khám phá các cơ hội phù hợp với kỹ năng của bạn.</p>
            <a href="{{ route('jobs.index') }}" class="btn-primary mt-5">Tìm việc làm</a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <ul class="divide-y divide-slate-100">
                @foreach ($applications as $application)
                    <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('jobs.show', $application->jobPost) }}" class="font-semibold text-slate-900 hover:text-blue-700">{{ $application->jobPost->title }}</a>
                            <p class="text-sm text-slate-500">{{ $application->jobPost->company_name }} · {{ $application->jobPost->location }}</p>
                            <p class="mt-1 text-xs text-slate-400">
                                Nộp {{ $application->created_at->format('d/m/Y H:i') }} · CV: {{ $application->cv_title }}
                                @if ($application->status_changed_at) · Cập nhật {{ $application->status_changed_at->diffForHumans() }} @endif
                            </p>
                            @foreach ($application->interviews as $interview)
                                <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                                    📅 Phỏng vấn <span class="font-semibold">{{ $interview->scheduled_at->format('H:i') }} ngày {{ $interview->scheduled_at->format('d/m/Y') }}</span> ({{ $interview->duration_minutes }} phút) · {{ $interview->type->label() }}
                                    @if ($interview->location)
                                        · @if (str_starts_with($interview->location, 'http'))<a href="{{ $interview->location }}" target="_blank" rel="noopener" class="font-semibold underline">Vào phòng họp</a>@else{{ $interview->location }}@endif
                                    @endif
                                    @if ($interview->note) <span class="mt-0.5 block text-xs text-amber-800">Ghi chú: {{ $interview->note }}</span> @endif
                                </p>
                            @endforeach
                        </div>
                        <span class="{{ $application->status->badgeClasses() }} self-start rounded-full px-3 py-1 text-xs font-semibold sm:self-center">{{ $application->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-6">{{ $applications->links() }}</div>
    @endif
@endsection
