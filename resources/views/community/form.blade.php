@extends('layouts.public')
@section('title', $post->exists ? 'Sửa bài viết' : 'Đăng bài')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <a href="{{ $post->exists ? route('community.show', $post) : route('community.index') }}" class="text-sm text-slate-500 hover:text-blue-700">&larr; Quay lại</a>
        <h1 class="mt-4 text-2xl font-bold text-slate-900">{{ $post->exists ? 'Sửa bài viết' : 'Đăng bài mới' }}</h1>

        <form method="POST" enctype="multipart/form-data"
              action="{{ $post->exists ? route('community.update', $post) : route('community.store') }}"
              class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @if ($post->exists) @method('PUT') @endif

            <div>
                <label for="title" class="form-label">Tiêu đề</label>
                <input id="title" name="title" maxlength="150" required value="{{ old('title', $post->title) }}" class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="form-label">Chủ đề</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($topics as $topic)
                        <label class="cursor-pointer">
                            <input type="radio" name="topic" value="{{ $topic->value }}" class="peer sr-only" required
                                   @checked(old('topic', $post->topic?->value ?? 'experience') === $topic->value)>
                            <span class="block rounded-full px-4 py-1.5 text-sm font-medium ring-1 ring-slate-200 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:ring-blue-600 peer-focus-visible:ring-4">{{ $topic->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('topic') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="body" class="form-label">Nội dung</label>
                <textarea id="body" name="body" rows="9" maxlength="10000" required class="form-input">{{ old('body', $post->body) }}</textarea>
                @error('body') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="form-label">Ảnh (tối đa {{ \App\Models\CommunityPost::MAX_IMAGES }} ảnh, JPG/PNG/WEBP, mỗi ảnh ≤ 2MB)</span>

                @if ($post->exists && $post->images->isNotEmpty())
                    <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ($post->images as $image)
                            <label class="group relative block cursor-pointer overflow-hidden rounded-lg ring-1 ring-slate-200">
                                <img src="{{ $image->url() }}" alt="" class="aspect-square w-full object-cover group-has-[:checked]:opacity-40">
                                <span class="absolute inset-x-0 bottom-0 flex items-center gap-1.5 bg-white/90 px-2 py-1 text-xs text-slate-700">
                                    <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" @checked(in_array($image->id, old('remove_images', [])))> Xoá ảnh
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                @php
                    // PHP drops the whole form when it exceeds post_max_size, so warn before uploading.
                    $postLimit = (string) ini_get('post_max_size');
                    $postLimitBytes = (int) $postLimit * match (strtoupper(substr($postLimit, -1))) { 'G' => 1 << 30, 'M' => 1 << 20, 'K' => 1 << 10, default => 1 };
                @endphp
                <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" data-image-input
                       data-max-total="{{ $postLimitBytes }}" data-max-total-label="{{ $postLimit }}"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <div data-image-preview class="mt-3 grid grid-cols-4 gap-3"></div>
                <p data-image-warning class="form-error hidden"></p>
                @error('images') <p class="form-error">{{ $message }}</p> @enderror
                @if (request()->boolean('upload_too_large'))
                    <p class="form-error">Tổng dung lượng tải lên vượt quá {{ $postLimit }} nên bài chưa được lưu. Vui lòng chọn ít ảnh hơn hoặc ảnh nhỏ hơn rồi đăng lại.</p>
                @endif
                @foreach ($errors->get('images.*') as $messages)
                    <p class="form-error">{{ $messages[0] }}</p>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ $post->exists ? route('community.show', $post) : route('community.index') }}" class="btn-secondary">Huỷ</a>
                <button class="btn-primary">{{ $post->exists ? 'Lưu thay đổi' : 'Đăng bài' }}</button>
            </div>
        </form>
    </section>

    <script>
        // Thumbnails of the newly chosen images before upload, and a warning when they are too big to send.
        (() => {
            const input = document.querySelector('[data-image-input]');
            const warning = document.querySelector('[data-image-warning]');
            const tooBig = () => {
                const limit = Number(input.dataset.maxTotal) * 0.95; // leave room for the text fields
                const total = [...input.files].reduce((sum, file) => sum + file.size, 0);
                return limit > 0 && total > limit;
            };
            input.addEventListener('change', () => {
                const preview = document.querySelector('[data-image-preview]');
                preview.replaceChildren();
                [...input.files].forEach((file) => {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.className = 'aspect-square w-full rounded-lg object-cover ring-1 ring-slate-200';
                    preview.append(img);
                });
                warning.textContent = `Tổng dung lượng ảnh vượt quá ${input.dataset.maxTotalLabel}. Vui lòng chọn ít ảnh hơn hoặc ảnh nhỏ hơn.`;
                warning.classList.toggle('hidden', !tooBig());
            });
            input.form.addEventListener('submit', (event) => {
                if (tooBig()) { event.preventDefault(); warning.classList.remove('hidden'); }
            });
        })();
    </script>
@endsection
