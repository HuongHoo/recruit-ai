@extends('layouts.app')
@section('title', 'Thông báo việc làm')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('candidate.job-alerts.store') }}" class="space-y-4 self-start rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            <div>
                <h2 class="font-semibold text-slate-900">Tạo thông báo mới</h2>
                <p class="mt-1 text-sm text-slate-500">Mỗi sáng chúng tôi gửi email cho bạn khi có việc làm mới phù hợp.</p>
            </div>
            <div>
                <label for="keyword" class="form-label">Từ khóa</label>
                <input id="keyword" name="keyword" value="{{ old('keyword') }}" placeholder="VD: Laravel, Kế toán..." class="form-input">
                @error('keyword') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="category" class="form-label">Ngành nghề</label>
                <select id="category" name="category" class="form-input">
                    <option value="">Tất cả</option>
                    @foreach (config('recruitment.job_categories') as $category)
                        <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @error('category') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="location" class="form-label">Địa điểm</label>
                <select id="location" name="location" class="form-input">
                    <option value="">Tất cả</option>
                    @foreach (config('recruitment.locations') as $location)
                        <option value="{{ $location }}" @selected(old('location') === $location)>{{ $location }}</option>
                    @endforeach
                </select>
                @error('location') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full">Đăng ký nhận thông báo</button>
            <p class="text-xs text-slate-500">Email gửi tới: <strong>{{ auth()->user()->email }}</strong> · tối đa {{ $maxAlerts }} thông báo.</p>
        </form>

        <section class="lg:col-span-2">
            <h2 class="mb-4 font-semibold text-slate-900">Thông báo của tôi ({{ $alerts->count() }}/{{ $maxAlerts }})</h2>

            @if ($alerts->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">Bạn chưa đăng ký thông báo việc làm nào.</div>
            @else
                <ul class="space-y-3">
                    @foreach ($alerts as $alert)
                        <li class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900">{{ $alert->summary() }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $alert->is_active ? 'Đang bật' : 'Đã tạm dừng' }}
                                    · {{ $alert->last_sent_at ? 'Gửi gần nhất '.$alert->last_sent_at->diffForHumans() : 'Chưa gửi email nào' }}
                                </p>
                            </div>
                            <a href="{{ route('jobs.index', array_filter(['keyword' => $alert->keyword, 'category' => $alert->category, 'location' => $alert->location])) }}" class="rounded-lg px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50">Xem việc</a>
                            <form method="POST" action="{{ route('candidate.job-alerts.update', $alert) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200">{{ $alert->is_active ? 'Tạm dừng' : 'Bật lại' }}</button>
                            </form>
                            <form method="POST" action="{{ route('candidate.job-alerts.destroy', $alert) }}" onsubmit="return confirm('Xóa thông báo này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50">Xóa</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
