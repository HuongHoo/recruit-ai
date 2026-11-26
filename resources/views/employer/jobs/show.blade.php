@extends('layouts.app')
@section('title', 'Hồ sơ ứng tuyển: '.$job->title)
@section('subtitle', 'Danh sách hồ sơ đã ứng tuyển vào tin này.')
@section('icon', 'briefcase')

@section('content')
    @php use App\Enums\ApplicationStatus; @endphp

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('employer.jobs.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Danh sách tin</a>
        <span class="flex-1"></span>
        <a href="{{ route('jobs.show', $job) }}" target="_blank" class="btn-secondary py-2">Xem tin</a>
        <a href="{{ route('employer.jobs.edit', $job) }}" class="btn-secondary py-2">Sửa tin</a>
    </div>

    <nav class="mb-5 flex flex-wrap gap-2 text-sm font-medium">
        <a href="{{ route('employer.jobs.show', $job) }}" @class([
            'rounded-full px-4 py-1.5',
            'bg-teal-600 text-white' => ! $currentStatus,
            'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $currentStatus,
        ])>Tất cả ({{ $statusCounts->sum() }})</a>
        @foreach (ApplicationStatus::cases() as $status)
            <a href="{{ route('employer.jobs.show', [$job, 'status' => $status->value]) }}" @class([
                'rounded-full px-4 py-1.5',
                'bg-teal-600 text-white' => $currentStatus === $status->value,
                'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' => $currentStatus !== $status->value,
            ])>{{ $status->label() }} ({{ $statusCounts[$status->value] ?? 0 }})</a>
        @endforeach
    </nav>

    @include('employer.applications._table', ['applications' => $applications])
@endsection
