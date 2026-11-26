@extends('layouts.app')
@section('title', $post->exists ? 'Sửa nội dung' : 'Tạo '.mb_strtolower($post->type->label()))

@section('content')
    @php $type = old('type', $post->type->value); @endphp
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('admin.posts.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Danh sách</a>

        <form method="POST" enctype="multipart/form-data"
              action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
              class="mt-4 space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @if ($post->exists) @method('PUT') @endif

            <fieldset>
                <legend class="form-label">Loại nội dung <span class="text-red-500">*</span></legend>
                <div class="flex gap-3">
                    @foreach (\App\Enums\PostType::cases() as $case)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm has-checked:border-blue-600 has-checked:bg-blue-50">
                            <input type="radio" name="type" value="{{ $case->value }}" @checked($type === $case->value) onchange="document.getElementById('event-fields').hidden = this.value !== 'event'">
                            {{ $case->label() }}
                        </label>
                    @endforeach
                </div>
                @error('type') <p class="form-error">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="title" class="form-label">Tiêu đề <span class="text-red-500">*</span></label>
                <input id="title" name="title" value="{{ old('title', $post->title) }}" required maxlength="255" class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="form-label">Chuyên mục</label>
                <input id="category" name="category" value="{{ old('category', $post->category) }}" maxlength="100" placeholder="VD: Đồng hành với trường đại học, Cẩm nang nghề nghiệp" class="form-input">
                @error('category') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div id="event-fields" class="grid gap-5 sm:grid-cols-2" @if ($type !== 'event') hidden @endif>
                <div>
                    <label for="event_date" class="form-label">Ngày diễn ra <span class="text-red-500">*</span></label>
                    <input id="event_date" type="date" name="event_date" value="{{ old('event_date', $post->event_date?->format('Y-m-d')) }}" class="form-input">
                    @error('event_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="event_location" class="form-label">Địa điểm</label>
                    <input id="event_location" name="event_location" value="{{ old('event_location', $post->event_location) }}" maxlength="255" class="form-input">
                    @error('event_location') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="excerpt" class="form-label">Mô tả ngắn <span class="text-red-500">*</span></label>
                <textarea id="excerpt" name="excerpt" rows="2" required maxlength="500" class="form-input">{{ old('excerpt', $post->excerpt) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Hiển thị trên thẻ ở trang chủ.</p>
                @error('excerpt') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="content" class="form-label">Nội dung <span class="text-red-500">*</span></label>
                <textarea id="content" name="content" rows="12" required class="form-input">{{ old('content', $post->content) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Mỗi đoạn cách nhau một dòng trống.</p>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cover" class="form-label">Ảnh bìa</label>
                @if ($post->coverUrl())
                    <div class="mb-2 flex items-center gap-4">
                        <img src="{{ $post->coverUrl() }}" alt="" class="h-20 w-36 rounded-lg object-cover">
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remove_cover" value="1"> Xoá ảnh hiện tại</label>
                    </div>
                @endif
                <input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="form-input">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG hoặc WEBP, tối đa 4MB. Nên dùng ảnh ngang 16:9.</p>
                @error('cover') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="published_at" class="form-label">Thời gian đăng</label>
                    <input id="published_at" type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                    <p class="mt-1 text-xs text-slate-500">Để trống = lưu nháp. Chọn ngày tương lai = hẹn giờ đăng.</p>
                    @error('published_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 self-center text-sm font-medium text-slate-700">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))>
                    Ghim nổi bật trên trang chủ
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('admin.posts.index') }}" class="btn-secondary">Huỷ</a>
                <button class="btn-primary">{{ $post->exists ? 'Lưu thay đổi' : 'Đăng' }}</button>
            </div>
        </form>
    </div>
@endsection
