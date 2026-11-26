@extends('layouts.app')
@section('title', $job->exists ? 'Sửa tin tuyển dụng' : 'Đăng tin tuyển dụng')
@section('subtitle', 'Điền thông tin vị trí; tin hiển thị công khai ngay sau khi lưu.')
@section('icon', 'briefcase')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('employer.jobs.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Danh sách tin</a>

        <form method="POST" action="{{ $job->exists ? route('employer.jobs.update', $job) : route('employer.jobs.store') }}"
              class="mt-4 space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @if ($job->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="title" class="form-label">Tiêu đề tin <span class="text-red-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $job->title) }}" required placeholder="VD: Lập trình viên PHP / Laravel" class="form-input">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="company_name" class="form-label">Tên công ty <span class="text-red-500">*</span></label>
                    <input id="company_name" name="company_name" value="{{ old('company_name', $job->company_name) }}" required class="form-input">
                    @error('company_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="category" class="form-label">Ngành nghề <span class="text-red-500">*</span></label>
                    <select id="category" name="category" required class="form-input">
                        <option value="">-- Chọn --</option>
                        @foreach (config('recruitment.job_categories') as $category)
                            <option value="{{ $category }}" @selected(old('category', $job->category) === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                    @error('category') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="location" class="form-label">Địa điểm <span class="text-red-500">*</span></label>
                    <select id="location" name="location" required class="form-input">
                        <option value="">-- Chọn --</option>
                        @foreach (config('recruitment.locations') as $location)
                            <option value="{{ $location }}" @selected(old('location', $job->location) === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                    @error('location') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="job_type" class="form-label">Hình thức <span class="text-red-500">*</span></label>
                    <select id="job_type" name="job_type" required class="form-input">
                        @foreach ($jobTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('job_type', $job->job_type?->value) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('job_type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="experience" class="form-label">Kinh nghiệm</label>
                    <select id="experience" name="experience" class="form-input">
                        <option value="">Không yêu cầu</option>
                        @foreach (config('recruitment.experience_levels') as $level)
                            <option value="{{ $level }}" @selected(old('experience', $job->experience) === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    @error('experience') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="salary_min" class="form-label">Lương từ (triệu VNĐ)</label>
                    <input id="salary_min" type="number" min="0" name="salary_min" value="{{ old('salary_min', $job->salary_min) }}" class="form-input">
                    @error('salary_min') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="salary_max" class="form-label">Lương đến (triệu VNĐ)</label>
                    <input id="salary_max" type="number" min="0" name="salary_max" value="{{ old('salary_max', $job->salary_max) }}" class="form-input">
                    <p class="mt-1 text-xs text-slate-400">Để trống cả hai nếu lương thỏa thuận.</p>
                    @error('salary_max') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            @foreach (['description' => ['Mô tả công việc', true], 'requirements' => ['Yêu cầu ứng viên', false], 'benefits' => ['Quyền lợi', false]] as $field => [$label, $required])
                <div>
                    <label for="{{ $field }}" class="form-label">{{ $label }} @if ($required) <span class="text-red-500">*</span> @endif</label>
                    <textarea id="{{ $field }}" name="{{ $field }}" rows="5" @required($required) class="form-input">{{ old($field, $job->{$field}) }}</textarea>
                    @error($field) <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="deadline" class="form-label">Hạn nộp hồ sơ</label>
                    <input id="deadline" type="date" name="deadline" value="{{ old('deadline', $job->deadline?->format('Y-m-d')) }}" class="form-input">
                    @error('deadline') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" class="form-label">Trạng thái</label>
                    <select id="status" name="status" class="form-input">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $job->status?->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('employer.jobs.index') }}" class="btn-secondary">Hủy</a>
                <button type="submit" class="btn-primary bg-teal-600 hover:bg-teal-700">{{ $job->exists ? 'Lưu thay đổi' : 'Đăng tin' }}</button>
            </div>
        </form>
    </div>
@endsection
