@extends('layouts.app')
@section('title', 'Việc đã lưu')

@section('content')
    @if ($jobs->isEmpty())
        <div class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
            <p class="font-semibold text-slate-800">Bạn chưa lưu việc làm nào</p>
            <p class="mt-1 max-w-sm text-sm text-slate-500">Bấm biểu tượng ♡ trên tin tuyển dụng để lưu lại và xem sau.</p>
            <a href="{{ route('jobs.index') }}" class="btn-primary mt-5">Tìm việc làm</a>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($jobs as $job)
                <div class="relative">
                    @include('jobs.partials.card', ['job' => $job, 'isSaved' => true])
                    @unless ($job->isVisible())
                        <span class="absolute top-3 right-14 rounded-full bg-slate-800 px-2.5 py-0.5 text-xs font-semibold text-white">Đã hết hạn</span>
                    @endunless
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $jobs->links() }}</div>
    @endif
@endsection
