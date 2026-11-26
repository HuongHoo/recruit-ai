{{-- One comment. Root comments also render their replies and a hidden reply form. Expects $comment, $post, $isReply. --}}
@php $viewer = auth()->user(); @endphp
<div id="comment-{{ $comment->id }}" class="scroll-mt-24" data-comment>
    <div class="rounded-xl bg-slate-50 px-4 py-3">
        @include('community._author', ['user' => $comment->author, 'time' => $comment->created_at, 'avatarClass' => 'size-8 text-[11px]'])
        <p class="mt-2 text-sm leading-relaxed break-words whitespace-pre-line text-slate-700">@if ($comment->replyTo)<span class="font-semibold text-blue-700">{{ '@'.$comment->replyTo->name }}</span> @endif{{ $comment->body }}</p>
    </div>

    <div class="mt-1 flex flex-wrap items-center gap-3 px-2 text-xs font-semibold text-slate-500">
        @if ($viewer?->can('interact', $post))
            <button type="button" class="hover:text-blue-700" data-reply-to="{{ $comment->id }}" data-reply-root="{{ $comment->parent_id ?? $comment->id }}" data-reply-name="{{ $comment->author?->name }}">Trả lời</button>
        @endif
        @if ($viewer?->can('update', $comment))
            <details class="group">
                <summary class="cursor-pointer list-none hover:text-blue-700 [&::-webkit-details-marker]:hidden">Sửa</summary>
                <form method="POST" action="{{ route('community.comments.update', $comment) }}" class="mt-2 flex w-full flex-col gap-2 sm:w-[32rem]">
                    @csrf @method('PUT')
                    <textarea name="body" rows="2" maxlength="2000" required class="form-input font-normal">{{ $comment->body }}</textarea>
                    <button class="btn-primary self-end px-3 py-1.5 text-xs">Lưu</button>
                </form>
            </details>
        @endif
        @if ($viewer?->can('delete', $comment))
            <form method="POST" action="{{ route('community.comments.destroy', $comment) }}" onsubmit="return confirm('Xoá bình luận này{{ $isReply ? '' : ' và các câu trả lời' }}?')">
                @csrf @method('DELETE')
                <button class="hover:text-rose-600">Xoá</button>
            </form>
        @endif
        @if ($viewer && $viewer->id !== $comment->user_id)
            <button type="button" class="font-normal hover:text-rose-600" data-report="comment" data-report-id="{{ $comment->id }}">Báo cáo</button>
        @endif
    </div>

    @unless ($isReply)
        <div class="mt-3 ml-6 space-y-3 border-l-2 border-slate-100 pl-4 sm:ml-10" data-replies="{{ $comment->id }}">
            @foreach ($comment->replies as $reply)
                @include('community._comment', ['comment' => $reply, 'isReply' => true])
            @endforeach
        </div>
    @endunless
</div>
