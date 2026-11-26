{{-- Post cover image, or a branded gradient when the admin did not upload one. --}}
@if ($post->coverUrl())
    <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" class="{{ $class ?? '' }} object-cover" loading="lazy">
@else
    <div class="{{ $class ?? '' }} grid place-items-center bg-gradient-to-br from-blue-950 via-blue-800 to-blue-500 p-4" aria-hidden="true">
        <span class="text-center text-sm font-bold tracking-wide text-white/80 uppercase">{{ $post->category ?? $post->type->label() }}</span>
    </div>
@endif
