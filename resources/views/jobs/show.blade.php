@extends('layouts.public')
@section('title', $job->title)

@section('content')
    <div class="bg-slate-50">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <a href="{{ route('jobs.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Tất cả việc làm</a>

                @if (session('status'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif

                <header class="rounded-2xl border border-slate-200 bg-white p-6">
                    <div class="flex items-start gap-4">
                        <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-blue-600 text-xl font-bold text-white">{{ mb_strtoupper(mb_substr($job->company_name, 0, 1)) }}</span>
                        <div class="min-w-0 flex-1">
                            <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $job->title }}</h1>
                            <p class="mt-1 text-slate-600">{{ $job->company_name }}</p>
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-4">
                        @foreach ([
                            'Mức lương' => $job->salaryLabel(),
                            'Địa điểm' => $job->location,
                            'Hình thức' => $job->job_type->label(),
                            'Kinh nghiệm' => $job->experience ?? 'Không yêu cầu',
                        ] as $label => $value)
                            <div>
                                <dt class="text-slate-500">{{ $label }}</dt>
                                <dd class="mt-0.5 font-semibold text-slate-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @unless ($job->isVisible())
                        <p class="mt-5 rounded-lg bg-amber-50 px-4 py-2.5 text-sm text-amber-800">Tin này đã đóng hoặc hết hạn — ứng viên không còn nhìn thấy.</p>
                    @endunless
                </header>

                @foreach (['Mô tả công việc' => $job->description, 'Yêu cầu ứng viên' => $job->requirements, 'Quyền lợi' => $job->benefits] as $heading => $body)
                    @if (filled($body))
                        <section class="rounded-2xl border border-slate-200 bg-white p-6">
                            <h2 class="font-semibold text-slate-900">{{ $heading }}</h2>
                            <div class="mt-3 text-sm leading-relaxed whitespace-pre-line text-slate-700">{{ $body }}</div>
                        </section>
                    @endif
                @endforeach
            </div>

            {{-- Apply sidebar --}}
            <aside class="space-y-6 lg:pt-9">
                @include('jobs.partials.application-support')

                <div id="ung-tuyen" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6">
                    @if ($job->deadline)
                        @php $daysLeft = (int) today()->diffInDays($job->deadline, false); @endphp
                        <p class="text-sm text-slate-500">
                            Hạn nộp hồ sơ: <strong class="text-slate-900">{{ $job->deadline->format('d/m/Y') }}</strong>
                            @if ($daysLeft >= 0)
                                <span @class(['ml-1 rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-red-100 text-red-700' => $daysLeft <= 3, 'bg-slate-100 text-slate-600' => $daysLeft > 3])>{{ $daysLeft === 0 ? 'Hết hạn hôm nay' : "Còn {$daysLeft} ngày" }}</span>
                            @endif
                        </p>
                    @endif

                    @guest
                        <a href="{{ route('login') }}" class="btn-primary mt-4 w-full">Đăng nhập để ứng tuyển</a>
                    @endguest

                    @if ($isCandidate)
                        @if ($application)
                            <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm">
                                <p class="text-slate-600">Bạn đã ứng tuyển {{ $application->created_at->diffForHumans() }} bằng CV <strong>{{ $application->cv_title }}</strong>.</p>
                                <p class="mt-2">Trạng thái: <span class="{{ $application->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $application->status->label() }}</span></p>
                                <a href="{{ route('candidate.applications.index') }}" class="mt-3 inline-block font-semibold text-blue-600 hover:text-blue-700">Theo dõi đơn ứng tuyển &rarr;</a>
                            </div>
                        @elseif ($cvs->isEmpty())
                            <p class="mt-4 text-sm text-slate-600">Bạn cần có ít nhất một CV để ứng tuyển.</p>
                            <a href="{{ route('candidate.cvs.index') }}" class="btn-primary mt-3 w-full">Tạo hoặc tải CV lên</a>
                        @else
                            <form method="POST" action="{{ route('candidate.applications.store', $job) }}" class="mt-4 space-y-4">
                                @csrf
                                <fieldset>
                                    <legend class="form-label">Chọn CV ứng tuyển</legend>
                                    <div class="space-y-2">
                                        @foreach ($cvs as $cv)
                                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm has-checked:border-blue-500 has-checked:bg-blue-50">
                                                <input type="radio" name="cv_id" value="{{ $cv->id }}" @checked((int) old('cv_id', $cvs->first()->id) === $cv->id) class="text-blue-600">
                                                <span class="min-w-0 flex-1 truncate">{{ $cv->title }}</span>
                                                @if ($cv->is_default) <span class="text-xs text-blue-600">Mặc định</span> @endif
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('cv_id') <p class="form-error">{{ $message }}</p> @enderror
                                </fieldset>
                                <div>
                                    <label for="cover_letter" class="form-label">Thư giới thiệu <span class="font-normal text-slate-400">(không bắt buộc)</span></label>
                                    <textarea id="cover_letter" name="cover_letter" rows="4" class="form-input" placeholder="Vài dòng giới thiệu bản thân và lý do bạn phù hợp...">{{ old('cover_letter') }}</textarea>
                                    @error('cover_letter') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <button type="submit" class="btn-primary w-full">Nộp hồ sơ ứng tuyển</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ $isSaved ? route('candidate.saved-jobs.destroy', $job) : route('candidate.saved-jobs.store', $job) }}" class="mt-3">
                            @csrf
                            @if ($isSaved) @method('DELETE') @endif
                            <button type="submit" class="btn-secondary w-full">{{ $isSaved ? '♥ Đã lưu — bỏ lưu' : '♡ Lưu việc làm' }}</button>
                        </form>
                    @endif
                </div>

                @if ($relatedJobs->isNotEmpty())
                    <div class="space-y-3">
                        <h2 class="font-semibold text-slate-900">Việc làm tương tự</h2>
                        @foreach ($relatedJobs as $related)
                            @include('jobs.partials.card', ['job' => $related])
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection
