@extends('layouts.app')
@section('title', $education->exists ? 'Sửa học vấn' : 'Thêm học vấn')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('candidate.profile.edit') }}#hoc-van" class="text-sm text-slate-500 hover:text-slate-800">&larr; Quay lại hồ sơ</a>

        <form method="POST" action="{{ $education->exists ? route('candidate.educations.update', $education) : route('candidate.educations.store') }}"
              class="mt-4 space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @if ($education->exists) @method('PUT') @endif

            <div>
                <label for="school" class="form-label">Trường / Cơ sở đào tạo <span class="text-red-500">*</span></label>
                <input id="school" name="school" value="{{ old('school', $education->school) }}" required placeholder="VD: Đại học Bách khoa Hà Nội" class="form-input">
                @error('school') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="major" class="form-label">Chuyên ngành</label>
                    <input id="major" name="major" value="{{ old('major', $education->major) }}" placeholder="VD: Công nghệ thông tin" class="form-input">
                    @error('major') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="degree" class="form-label">Bằng cấp</label>
                    <select id="degree" name="degree" class="form-input">
                        <option value="">-- Chọn --</option>
                        @foreach (config('recruitment.degrees') as $degree)
                            <option value="{{ $degree }}" @selected(old('degree', $education->degree) === $degree)>{{ $degree }}</option>
                        @endforeach
                    </select>
                    @error('degree') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="start_date" class="form-label">Bắt đầu <span class="text-red-500">*</span></label>
                    <input id="start_date" type="month" name="start_date" required value="{{ old('start_date') ? substr(old('start_date'), 0, 7) : $education->start_date?->format('Y-m') }}" class="form-input">
                    @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="end_date" class="form-label">Kết thúc <span class="font-normal text-slate-400">(để trống nếu đang học)</span></label>
                    <input id="end_date" type="month" name="end_date" value="{{ old('end_date') ? substr(old('end_date'), 0, 7) : $education->end_date?->format('Y-m') }}" class="form-input">
                    @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="form-label">Mô tả</label>
                <textarea id="description" name="description" rows="4" placeholder="Thành tích, GPA, hoạt động nổi bật..." class="form-input">{{ old('description', $education->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('candidate.profile.edit') }}#hoc-van" class="btn-secondary">Hủy</a>
                <button type="submit" class="btn-primary">{{ $education->exists ? 'Lưu thay đổi' : 'Thêm học vấn' }}</button>
            </div>
        </form>
    </div>
@endsection
