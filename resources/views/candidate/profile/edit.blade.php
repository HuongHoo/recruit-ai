@extends('layouts.app')
@section('title', 'Hồ sơ cá nhân')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        {{-- Quick nav --}}
        <nav class="flex gap-2 overflow-x-auto text-sm font-medium">
            @foreach (['thong-tin' => 'Thông tin cá nhân', 'mong-muon' => 'Mong muốn công việc', 'hoc-van' => 'Học vấn', 'kinh-nghiem' => 'Kinh nghiệm'] as $anchor => $label)
                <a href="#{{ $anchor }}" class="shrink-0 rounded-full border border-slate-200 bg-white px-4 py-1.5 text-slate-600 hover:border-blue-300 hover:text-blue-700">{{ $label }}</a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('candidate.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Personal info --}}
            <section id="thong-tin" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white">
                <header class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Thông tin cá nhân</h2>
                    <p class="text-sm text-slate-500">Nhà tuyển dụng sẽ thấy các thông tin này trong CV của bạn.</p>
                </header>
                <div class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="form-label">Họ và tên <span class="text-red-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-input">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="headline" class="form-label">Vị trí / Chức danh mong muốn</label>
                        <input id="headline" name="headline" value="{{ old('headline', $profile->headline) }}" placeholder="VD: Lập trình viên PHP" class="form-input">
                        @error('headline') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input value="{{ $user->email }}" disabled class="form-input bg-slate-50 text-slate-500">
                    </div>
                    <div>
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input id="phone" name="phone" value="{{ old('phone', $profile->phone) }}" placeholder="0912 345 678" class="form-input">
                        @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="date_of_birth" class="form-label">Ngày sinh</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $profile->date_of_birth?->format('Y-m-d')) }}" class="form-input">
                        @error('date_of_birth') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <span class="form-label">Giới tính</span>
                        <div class="flex gap-2">
                            @foreach (config('recruitment.genders') as $value => $label)
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" name="gender" value="{{ $value }}" class="peer sr-only" @checked(old('gender', $profile->gender) === $value)>
                                    <span class="block rounded-lg border border-slate-300 py-2.5 text-center text-sm peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:font-semibold peer-checked:text-blue-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <input id="address" name="address" value="{{ old('address', $profile->address) }}" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành" class="form-input">
                        @error('address') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="summary" class="form-label">Giới thiệu bản thân</label>
                        <textarea id="summary" name="summary" rows="4" placeholder="Tóm tắt ngắn về kinh nghiệm, thế mạnh và mục tiêu của bạn..." class="form-input">{{ old('summary', $profile->summary) }}</textarea>
                        @error('summary') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Job preferences --}}
            <section id="mong-muon" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white">
                <header class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Mong muốn công việc & Kỹ năng</h2>
                    <p class="text-sm text-slate-500">Giúp hệ thống gợi ý việc làm phù hợp hơn với bạn.</p>
                </header>
                <div class="space-y-6 p-6">
                    <div>
                        <span class="form-label">Mức lương mong muốn (triệu VNĐ / tháng)</span>
                        <div class="flex items-center gap-3">
                            <input type="number" name="desired_salary_min" min="0" max="1000" value="{{ old('desired_salary_min', $profile->desired_salary_min) }}" placeholder="Từ" class="form-input" aria-label="Lương tối thiểu">
                            <span class="text-slate-400">—</span>
                            <input type="number" name="desired_salary_max" min="0" max="1000" value="{{ old('desired_salary_max', $profile->desired_salary_max) }}" placeholder="Đến" class="form-input" aria-label="Lương tối đa">
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">Để trống nếu bạn muốn thỏa thuận.</p>
                        @error('desired_salary_min') <p class="form-error">{{ $message }}</p> @enderror
                        @error('desired_salary_max') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="form-label">Nơi muốn làm việc <span class="font-normal text-slate-400">(tối đa 5)</span></span>
                        @php $selectedLocations = old('desired_locations', $profile->desired_locations ?? []); @endphp
                        <div class="flex flex-wrap gap-2">
                            @foreach (config('recruitment.locations') as $location)
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="desired_locations[]" value="{{ $location }}" class="peer sr-only" @checked(in_array($location, $selectedLocations, true))>
                                    <span class="block rounded-full border border-slate-300 px-3.5 py-1.5 text-sm text-slate-600 transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white hover:border-blue-300">{{ $location }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('desired_locations') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="skills" class="form-label">Kỹ năng</label>
                        <input id="skills" name="skills" value="{{ old('skills', implode(', ', $profile->skills ?? [])) }}" placeholder="VD: PHP, Laravel, MySQL, Giao tiếp, Tiếng Anh" class="form-input">
                        <p class="mt-1.5 text-xs text-slate-500">Phân cách các kỹ năng bằng dấu phẩy.</p>
                        <div id="skill-preview" class="mt-3 flex flex-wrap gap-2"></div>
                        @error('skills') <p class="form-error">{{ $message }}</p> @enderror
                        @error('skills.*') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="sticky bottom-4 z-10 flex justify-end">
                <button type="submit" class="btn-primary px-6 shadow-lg shadow-blue-600/30">Lưu thông tin</button>
            </div>
        </form>

        {{-- Education --}}
        <section id="hoc-van" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white">
            <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Học vấn</h2>
                    <p class="text-sm text-slate-500">Trường, chuyên ngành và bằng cấp của bạn.</p>
                </div>
                <a href="{{ route('candidate.educations.create') }}" class="btn-secondary shrink-0">+ Thêm</a>
            </header>
            <ul class="divide-y divide-slate-100">
                @forelse ($educations as $education)
                    <li class="flex gap-4 px-6 py-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-600">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14 3 9l9-5 9 5-9 5Zm0 0v6m-5-8.5V16c0 1.5 2.2 3 5 3s5-1.5 5-3v-4.5"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-slate-900">{{ $education->school }}</h3>
                            <p class="text-sm text-slate-600">{{ collect([$education->degree, $education->major])->filter()->join(' · ') }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $education->periodLabel() }}</p>
                            @if ($education->description)
                                <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $education->description }}</p>
                            @endif
                        </div>
                        @include('candidate.partials.row-actions', [
                            'editUrl' => route('candidate.educations.edit', $education),
                            'deleteUrl' => route('candidate.educations.destroy', $education),
                            'confirm' => 'Xóa học vấn này?',
                        ])
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500">Chưa có thông tin học vấn.</li>
                @endforelse
            </ul>
        </section>

        {{-- Experience --}}
        <section id="kinh-nghiem" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white">
            <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Kinh nghiệm làm việc</h2>
                    <p class="text-sm text-slate-500">Các công việc bạn đã và đang làm.</p>
                </div>
                <a href="{{ route('candidate.experiences.create') }}" class="btn-secondary shrink-0">+ Thêm</a>
            </header>
            <ul class="divide-y divide-slate-100">
                @forelse ($experiences as $experience)
                    <li class="flex gap-4 px-6 py-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1ZM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="flex flex-wrap items-center gap-2 font-semibold text-slate-900">
                                {{ $experience->position }}
                                @if ($experience->is_current)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">Hiện tại</span>
                                @endif
                            </h3>
                            <p class="text-sm text-slate-600">{{ $experience->company }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $experience->periodLabel() }}</p>
                            @if ($experience->description)
                                <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $experience->description }}</p>
                            @endif
                        </div>
                        @include('candidate.partials.row-actions', [
                            'editUrl' => route('candidate.experiences.edit', $experience),
                            'deleteUrl' => route('candidate.experiences.destroy', $experience),
                            'confirm' => 'Xóa kinh nghiệm này?',
                        ])
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500">Chưa có kinh nghiệm làm việc. Sinh viên mới ra trường có thể bỏ qua mục này.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <script>
        (() => {
            const input = document.getElementById('skills');
            const preview = document.getElementById('skill-preview');
            const render = () => {
                preview.replaceChildren(...input.value.split(',').map((s) => s.trim()).filter(Boolean).map((skill) => {
                    const chip = document.createElement('span');
                    chip.className = 'rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700';
                    chip.textContent = skill;
                    return chip;
                }));
            };
            input.addEventListener('input', render);
            render();
        })();
    </script>
@endsection
