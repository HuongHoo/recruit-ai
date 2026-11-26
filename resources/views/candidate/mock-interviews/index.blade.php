@extends('layouts.app')
@section('title', 'Luyện phỏng vấn với AI')

@section('content')
    @if ($interviews->isEmpty())
        <div class="flex flex-col items-center rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
            <span class="grid size-16 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-xl font-bold text-white">AI</span>
            <p class="mt-4 font-semibold text-slate-800">Bạn chưa luyện phỏng vấn lần nào</p>
            <p class="mt-1 max-w-md text-sm text-slate-500">Mở một tin tuyển dụng, chọn <strong>"Phỏng vấn mô phỏng với AI"</strong> trong ô Hỗ trợ ứng tuyển để AI hỏi bạn như một buổi phỏng vấn thật.</p>
            <a href="{{ route('jobs.index') }}" class="btn-primary mt-5 rounded-full">Tìm việc làm để luyện</a>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <ul class="divide-y divide-slate-100">
                @foreach ($interviews as $interview)
                    <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">{{ $interview->jobPost->title }}</p>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $interview->jobPost->company_name }} · {{ $interview->cv_title }} · {{ $interview->languageName() }} · {{ $interview->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="{{ $interview->status->badgeClasses() }} self-start rounded-full px-2.5 py-0.5 text-xs font-semibold sm:self-center">{{ $interview->status->label() }}</span>
                        @if ($interview->isCompleted())
                            <span class="{{ $interview->verdict()['classes'] }} w-16 text-lg font-bold sm:text-right">{{ $interview->score }}<span class="text-xs font-medium text-slate-400">/100</span></span>
                            <a href="{{ route('candidate.mock-interviews.show', $interview) }}" class="btn-secondary rounded-full">Xem kết quả</a>
                        @else
                            <span class="w-16 text-sm text-slate-500 sm:text-right">{{ $interview->answeredCount() }}/{{ count($interview->questions) }} câu</span>
                            <a href="{{ route('candidate.mock-interviews.session', $interview) }}" class="btn-primary rounded-full">Tiếp tục</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="mt-6">{{ $interviews->links() }}</div>
    @endif
@endsection
