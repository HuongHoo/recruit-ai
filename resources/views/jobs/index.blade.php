@extends('layouts.public')
@section('title', 'Tìm việc làm')

@section('content')
    <section class="bg-gradient-to-br from-blue-600 to-indigo-700">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            <h1 class="text-2xl font-bold text-white sm:text-3xl">Tìm việc làm phù hợp với bạn</h1>
            <form method="GET" action="{{ route('jobs.index') }}" class="mt-6 grid gap-3 rounded-2xl bg-white p-3 shadow-xl sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto]">
                <input name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Vị trí, công ty, kỹ năng..." class="form-input" aria-label="Từ khóa">
                <select name="category" class="form-input" aria-label="Ngành nghề">
                    <option value="">Tất cả ngành nghề</option>
                    @foreach (config('recruitment.job_categories') as $category)
                        <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <select name="location" class="form-input" aria-label="Địa điểm">
                    <option value="">Tất cả địa điểm</option>
                    @foreach (config('recruitment.locations') as $location)
                        <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>
                    @endforeach
                </select>
                <select name="job_type" class="form-input" aria-label="Hình thức">
                    <option value="">Mọi hình thức</option>
                    @foreach ($jobTypes as $type)
                        <option value="{{ $type->value }}" @selected(($filters['job_type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Tìm kiếm</button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <p class="text-sm text-slate-600"><strong class="text-slate-900">{{ $jobs->total() }}</strong> việc làm phù hợp</p>

            @if (auth()->user()?->isCandidate() && array_filter($filters))
                <form method="POST" action="{{ route('candidate.job-alerts.store') }}">
                    @csrf
                    <input type="hidden" name="keyword" value="{{ $filters['keyword'] ?? '' }}">
                    <input type="hidden" name="category" value="{{ $filters['category'] ?? '' }}">
                    <input type="hidden" name="location" value="{{ $filters['location'] ?? '' }}">
                    <button type="submit" class="btn-secondary py-2">🔔 Nhận thông báo cho tìm kiếm này</button>
                </form>
            @endif
        </div>

        @if ($jobs->isEmpty())
            <div class="rounded-2xl border border-slate-200 px-6 py-16 text-center">
                <p class="font-semibold text-slate-800">Không tìm thấy việc làm phù hợp</p>
                <p class="mt-1 text-sm text-slate-500">Thử bỏ bớt bộ lọc hoặc dùng từ khóa khác.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($jobs as $job)
                    @if (auth()->user()?->isCandidate())
                        @include('jobs.partials.card', ['job' => $job, 'isSaved' => in_array($job->id, $savedJobIds, true)])
                    @else
                        @include('jobs.partials.card', ['job' => $job])
                    @endif
                @endforeach
            </div>
            <div class="mt-8">{{ $jobs->links() }}</div>
        @endif
    </section>
@endsection
