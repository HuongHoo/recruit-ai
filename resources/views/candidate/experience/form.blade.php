@extends('layouts.app')
@section('title', $experience->exists ? 'Sửa kinh nghiệm' : 'Thêm kinh nghiệm')

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('candidate.profile.edit') }}#kinh-nghiem" class="text-sm text-slate-500 hover:text-slate-800">&larr; Quay lại hồ sơ</a>

        <form method="POST" action="{{ $experience->exists ? route('candidate.experiences.update', $experience) : route('candidate.experiences.store') }}"
              class="mt-4 space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @if ($experience->exists) @method('PUT') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="position" class="form-label">Vị trí / Chức danh <span class="text-red-500">*</span></label>
                    <input id="position" name="position" value="{{ old('position', $experience->position) }}" required placeholder="VD: Nhân viên kinh doanh" class="form-input">
                    @error('position') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="company" class="form-label">Công ty <span class="text-red-500">*</span></label>
                    <input id="company" name="company" value="{{ old('company', $experience->company) }}" required placeholder="VD: Công ty CP ABC" class="form-input">
                    @error('company') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="start_date" class="form-label">Bắt đầu <span class="text-red-500">*</span></label>
                    <input id="start_date" type="month" name="start_date" required value="{{ old('start_date') ? substr(old('start_date'), 0, 7) : $experience->start_date?->format('Y-m') }}" class="form-input">
                    @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="end_date" class="form-label">Kết thúc</label>
                    <input id="end_date" type="month" name="end_date" value="{{ old('end_date') ? substr(old('end_date'), 0, 7) : $experience->end_date?->format('Y-m') }}" class="form-input disabled:bg-slate-100">
                    @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_current" value="0">
                <input id="is_current" type="checkbox" name="is_current" value="1" class="size-4 rounded border-slate-300" @checked(old('is_current', $experience->is_current))>
                Tôi đang làm việc tại đây
            </label>

            <div>
                <label for="description" class="form-label">Mô tả công việc</label>
                <textarea id="description" name="description" rows="5" placeholder="- Nhiệm vụ chính&#10;- Thành tích đạt được&#10;- Công cụ / công nghệ sử dụng" class="form-input">{{ old('description', $experience->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('candidate.profile.edit') }}#kinh-nghiem" class="btn-secondary">Hủy</a>
                <button type="submit" class="btn-primary">{{ $experience->exists ? 'Lưu thay đổi' : 'Thêm kinh nghiệm' }}</button>
            </div>
        </form>
    </div>

    <script>
        (() => {
            const current = document.getElementById('is_current');
            const end = document.getElementById('end_date');
            const sync = () => { end.disabled = current.checked; if (current.checked) end.value = ''; };
            current.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
