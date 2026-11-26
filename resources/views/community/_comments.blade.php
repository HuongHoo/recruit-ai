{{-- Comment form + two-level comment list. Forms post via fetch and insert the returned HTML without reloading. --}}
@php $viewer = auth()->user(); @endphp

@if ($viewer?->can('interact', $post))
    <form method="POST" action="{{ route('community.comments.store', $post) }}" class="mt-4 flex flex-col gap-2" data-comment-form>
        @csrf
        <textarea name="body" rows="3" maxlength="2000" required placeholder="Viết bình luận…" class="form-input"></textarea>
        @error('body') <p class="form-error">{{ $message }}</p> @enderror
        <p class="form-error hidden" data-form-error></p>
        <button class="btn-primary self-end">Gửi bình luận</button>
    </form>
@endif

<div class="mt-6 space-y-5" data-root-comments>
    @forelse ($post->rootComments as $comment)
        @include('community._comment', ['isReply' => false])
    @empty
        <p class="text-sm text-slate-500" data-no-comments>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
    @endforelse
</div>

@if ($viewer?->can('interact', $post))
    {{-- Reply form, moved under the comment whose "Trả lời" was clicked. --}}
    <template data-reply-template>
        <form method="POST" action="{{ route('community.comments.store', $post) }}" class="flex flex-col gap-2" data-comment-form data-reply-form>
            @csrf
            <input type="hidden" name="parent_id">
            <textarea name="body" rows="2" maxlength="2000" required class="form-input"></textarea>
            <p class="form-error hidden" data-form-error></p>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secondary px-3 py-1.5 text-xs" data-reply-cancel>Huỷ</button>
                <button class="btn-primary px-3 py-1.5 text-xs">Trả lời</button>
            </div>
        </form>
    </template>

    <script>
        (() => {
            const section = document.getElementById('binh-luan');
            const template = section.querySelector('[data-reply-template]');
            const count = document.querySelector('[data-comment-count]');

            section.addEventListener('click', (event) => {
                const replyButton = event.target.closest('[data-reply-to]');
                if (replyButton) {
                    section.querySelector('[data-reply-form]')?.remove();
                    const form = template.content.firstElementChild.cloneNode(true);
                    form.querySelector('[name=parent_id]').value = replyButton.dataset.replyTo;
                    form.querySelector('textarea').placeholder = 'Trả lời ' + replyButton.dataset.replyName + '…';
                    section.querySelector(`[data-replies="${replyButton.dataset.replyRoot}"]`).append(form);
                    form.querySelector('textarea').focus();
                }
                if (event.target.closest('[data-reply-cancel]')) {
                    event.target.closest('form').remove();
                }
            });

            section.addEventListener('submit', async (event) => {
                const form = event.target.closest('[data-comment-form]');
                if (!form) return;
                event.preventDefault();
                const button = form.querySelector('button:not([type=button])');
                const error = form.querySelector('[data-form-error]');
                button.disabled = true;
                error.classList.add('hidden');
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        error.textContent = response.status === 429
                            ? 'Bạn bình luận hơi nhanh, đợi một chút nhé.'
                            : (data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Không gửi được bình luận.'));
                        error.classList.remove('hidden');
                        return;
                    }
                    const holder = document.createElement('div');
                    holder.innerHTML = data.html;
                    const node = holder.firstElementChild;
                    section.querySelector('[data-no-comments]')?.remove();
                    if (data.parent_id) {
                        section.querySelector(`[data-replies="${data.parent_id}"]`).append(node);
                        form.remove();
                    } else {
                        section.querySelector('[data-root-comments]').append(node);
                        form.reset();
                    }
                    count.textContent = data.comments_count;
                    node.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch {
                    error.textContent = 'Mất kết nối, vui lòng thử lại.';
                    error.classList.remove('hidden');
                } finally {
                    button.disabled = false;
                }
            });
        })();
    </script>
@endif
