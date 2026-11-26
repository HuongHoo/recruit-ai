{{-- Like + report buttons on the post page, and the shared report dialog. Expects $post, $liked. --}}
@php $viewer = auth()->user(); @endphp

@if ($viewer?->can('interact', $post))
    <button type="button" data-like-url="{{ route('community.like', $post) }}" aria-pressed="{{ $liked ? 'true' : 'false' }}"
            class="group inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-slate-600 hover:bg-rose-50 hover:text-rose-600 aria-pressed:text-rose-600">
        <svg class="size-5 group-aria-pressed:fill-current" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
        Thích <span data-like-count>{{ $post->likes_count }}</span>
    </button>
@else
    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100">
        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
        Thích {{ $post->likes_count }}
    </a>
@endif
<a href="#binh-luan" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100">
    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14Z"/></svg>
    Bình luận
</a>
@if ($viewer && $viewer->id !== $post->user_id && $viewer->can('interact', $post))
    <button type="button" data-report="post" data-report-id="{{ $post->id }}" class="rounded-lg px-3 py-1.5 font-medium text-slate-500 hover:bg-slate-100 hover:text-rose-600">Báo cáo</button>
@endif

@if ($viewer?->can('interact', $post))
    @push('community-scripts')
        <dialog data-report-dialog class="m-auto w-[min(28rem,92vw)] rounded-2xl p-0 backdrop:bg-slate-950/50">
            <form class="space-y-4 p-6" data-report-form>
                <h3 class="text-lg font-bold text-slate-900">Báo cáo nội dung</h3>
                <p class="text-sm text-slate-500">Cho quản trị viên biết vì sao nội dung này không phù hợp.</p>
                <input type="hidden" name="type"><input type="hidden" name="id">
                <textarea name="reason" rows="3" maxlength="500" required class="form-input" placeholder="Ví dụ: spam, quảng cáo, ngôn từ xúc phạm…"></textarea>
                <p class="hidden text-sm" data-report-status></p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn-secondary" data-report-close>Đóng</button>
                    <button class="btn-primary">Gửi báo cáo</button>
                </div>
            </form>
        </dialog>
        <script>
            (() => {
                const token = @json(csrf_token());
                const post = (url, body) => fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify(body ?? {}),
                });

                const like = document.querySelector('[data-like-url]');
                like?.addEventListener('click', async () => {
                    like.disabled = true;
                    try {
                        const response = await post(like.dataset.likeUrl);
                        if (response.ok) {
                            const data = await response.json();
                            like.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
                            like.querySelector('[data-like-count]').textContent = data.likes_count;
                        }
                    } finally {
                        like.disabled = false;
                    }
                });

                const dialog = document.querySelector('[data-report-dialog]');
                const form = dialog.querySelector('[data-report-form]');
                const status = form.querySelector('[data-report-status]');
                document.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-report]');
                    if (button) {
                        form.reset();
                        status.className = 'hidden text-sm';
                        form.querySelector('[name=type]').value = button.dataset.report;
                        form.querySelector('[name=id]').value = button.dataset.reportId;
                        dialog.showModal();
                    }
                    if (event.target.closest('[data-report-close]')) dialog.close();
                });
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const response = await post(@json(route('community.report')), Object.fromEntries(new FormData(form)));
                    const data = await response.json().catch(() => ({}));
                    status.textContent = response.ok ? data.message
                        : (response.status === 429 ? 'Bạn gửi hơi nhiều báo cáo, thử lại sau nhé.' : (data.errors ? Object.values(data.errors)[0][0] : 'Không gửi được báo cáo.'));
                    status.className = 'text-sm ' + (response.ok ? 'text-emerald-700' : 'text-rose-600');
                    if (response.ok) setTimeout(() => dialog.close(), 1200);
                });
            })();
        </script>
    @endpush
@endif
