{{-- Floating chat assistant. History lives in this browser only (localStorage); the server is stateless. --}}
<div id="chat-assistant" class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-3 sm:right-6 sm:bottom-6">
    <section data-chat-panel hidden
             class="fixed inset-x-2 top-16 bottom-20 flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/20 sm:static sm:h-[560px] sm:max-h-[calc(100vh-7rem)] sm:w-[380px]"
             aria-label="Trợ lý {{ config('app.name') }}">
        <header class="flex items-center gap-3 bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 text-white">
            <span class="grid size-9 place-items-center rounded-full bg-white/20">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.8 16.2 8 21l4.2-2.8H19a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11.2a2 2 0 0 0 2 2h4.8ZM8 9h.01M12 9h.01M16 9h.01"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">Trợ lý {{ config('app.name') }}</p>
                <p class="text-xs text-blue-100">Hỏi về việc làm, CV, phỏng vấn…</p>
            </div>
            <button type="button" data-chat-reset class="rounded-lg px-2 py-1 text-xs font-medium text-blue-50 hover:bg-white/15" title="Bắt đầu cuộc trò chuyện mới">Cuộc trò chuyện mới</button>
            <button type="button" data-chat-close class="grid size-8 place-items-center rounded-lg text-xl hover:bg-white/15" aria-label="Đóng">&times;</button>
        </header>

        <div data-chat-log class="flex-1 space-y-3 overflow-y-auto bg-slate-50 px-4 py-4 text-sm" aria-live="polite"></div>

        <div data-chat-suggestions class="flex flex-wrap gap-2 border-t border-slate-100 bg-white px-4 pt-3">
            @foreach (['Tìm việc IT ở TP.HCM', 'Cách tạo Video CV?', 'Mẹo phỏng vấn'] as $suggestion)
                <button type="button" data-chat-suggestion class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">{{ $suggestion }}</button>
            @endforeach
        </div>

        <form data-chat-form class="flex items-end gap-2 bg-white p-3">
            <textarea data-chat-input rows="1" maxlength="1000" placeholder="Nhập câu hỏi…" aria-label="Nhập câu hỏi"
                      class="form-input max-h-28 min-h-[42px] resize-none py-2.5"></textarea>
            <button class="btn-primary h-[42px] shrink-0 px-3" aria-label="Gửi">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3 21l18-9L3 3l3 9Zm0 0h7"/></svg>
            </button>
        </form>
    </section>

    <button type="button" data-chat-toggle aria-expanded="false" aria-label="Mở trợ lý"
            class="grid size-14 place-items-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-600/40 transition hover:scale-105 focus-visible:ring-4 focus-visible:ring-blue-500/40 focus-visible:outline-none">
        <svg data-icon-open class="size-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 3-3h11a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14Z"/></svg>
        <svg data-icon-close class="hidden size-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
</div>

<script>
    (() => {
        const root = document.getElementById('chat-assistant');
        const panel = root.querySelector('[data-chat-panel]');
        const log = root.querySelector('[data-chat-log]');
        const form = root.querySelector('[data-chat-form]');
        const input = root.querySelector('[data-chat-input]');
        const toggle = root.querySelector('[data-chat-toggle]');
        const suggestions = root.querySelector('[data-chat-suggestions]');
        const endpoint = @json(route('assistant.chat'));
        const csrf = @json(csrf_token());
        const origin = window.location.origin;
        const STORAGE_KEY = 'recruitment-chat-history';
        const MAX_SENT = {{ \App\Http\Requests\ChatAssistantRequest::MAX_MESSAGES }};
        const GREETING = 'Xin chào! Mình là trợ lý của {{ config('app.name') }}. Bạn có thể hỏi mình tìm việc (ví dụ: "việc kế toán ở Hà Nội lương trên 15 triệu"), cách dùng các tính năng, hoặc mẹo viết CV và phỏng vấn nhé.';

        let history = load();
        let busy = false;

        function load() {
            try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch { return []; }
        }
        function save() {
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(history.slice(-30))); } catch { /* storage unavailable */ }
        }

        // Escape first, then allow a tiny Markdown subset. Links only to this site.
        function render(text) {
            const escaped = text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const inline = (line) => line
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (match, label, href) => {
                    const decoded = href.replace(/&amp;/g, '&');
                    const safe = decoded.startsWith('/') && !decoded.startsWith('//') || decoded.startsWith(origin + '/') || decoded === origin;
                    return safe ? `<a href="${href}" class="font-semibold text-blue-700 underline">${label}</a>` : label;
                });
            let html = '';
            let inList = false;
            for (const line of escaped.split('\n')) {
                const item = line.match(/^\s*[-*•]\s+(.*)$/);
                if (item) {
                    if (!inList) { html += '<ul class="my-1 list-disc space-y-0.5 pl-5">'; inList = true; }
                    html += `<li>${inline(item[1])}</li>`;
                    continue;
                }
                if (inList) { html += '</ul>'; inList = false; }
                html += line.trim() === '' ? '<div class="h-2"></div>' : `<p>${inline(line)}</p>`;
            }
            return html + (inList ? '</ul>' : '');
        }

        function bubble(role, text) {
            const row = document.createElement('div');
            row.className = role === 'user' ? 'flex justify-end' : 'flex justify-start';
            const box = document.createElement('div');
            box.className = role === 'user'
                ? 'max-w-[85%] rounded-2xl rounded-br-md bg-blue-600 px-3.5 py-2 text-white whitespace-pre-line break-words'
                : 'max-w-[90%] rounded-2xl rounded-bl-md bg-white px-3.5 py-2 text-slate-700 ring-1 ring-slate-200 break-words';
            if (role === 'user') box.textContent = text; else box.innerHTML = render(text);
            row.append(box);
            log.append(row);
            return box;
        }

        function jobCards(jobs, seeAll) {
            const wrap = document.createElement('div');
            wrap.className = 'space-y-2';
            for (const job of jobs) {
                const card = document.createElement('a');
                card.href = job.url;
                card.className = 'block rounded-xl bg-white px-3.5 py-2.5 ring-1 ring-slate-200 transition hover:ring-blue-400';
                const title = document.createElement('p');
                title.className = 'font-semibold text-slate-900';
                title.textContent = job.title;
                const meta = document.createElement('p');
                meta.className = 'mt-0.5 text-xs text-slate-500';
                meta.textContent = `${job.company} · ${job.salary} · ${job.location}`;
                card.append(title, meta);
                wrap.append(card);
            }
            if (seeAll) {
                const link = document.createElement('a');
                link.href = seeAll;
                link.className = 'inline-block text-xs font-semibold text-blue-700 hover:underline';
                link.textContent = 'Xem tất cả →';
                wrap.append(link);
            }
            log.append(wrap);
        }

        function draw() {
            log.replaceChildren();
            bubble('assistant', GREETING);
            for (const message of history) {
                bubble(message.role, message.text);
                if (message.jobs?.length || message.see_all_url) jobCards(message.jobs || [], message.see_all_url);
            }
            suggestions.hidden = history.length > 0;
            log.scrollTop = log.scrollHeight;
        }

        async function send(text) {
            text = text.trim().slice(0, 1000);
            if (!text || busy) return;
            busy = true;
            history.push({ role: 'user', text });
            save();
            draw();
            const typing = bubble('assistant', 'Đang trả lời…');
            typing.classList.add('animate-pulse', 'text-slate-400');
            log.scrollTop = log.scrollHeight;

            let message;
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ messages: history.slice(-MAX_SENT).map(({ role, text }) => ({ role, text: text.slice(0, 1000) })) }),
                });
                const data = await response.json().catch(() => ({}));
                if (response.status === 429) message = { text: 'Bạn gửi hơi nhanh, đợi một chút nhé.' };
                else if (response.status === 419) message = { text: 'Phiên làm việc đã hết hạn, bạn tải lại trang giúp mình nhé.' };
                else if (response.status === 422) message = { text: 'Tin nhắn chưa hợp lệ (tối đa 1000 ký tự). Bạn thử lại nhé.' };
                else message = { text: data.reply || 'Trợ lý đang bận, bạn thử lại sau ít phút nhé.', jobs: data.jobs, see_all_url: data.see_all_url, saved: response.ok };
            } catch {
                message = { text: 'Mất kết nối, bạn kiểm tra mạng rồi thử lại nhé.' };
            }

            // Only real answers become part of the conversation sent back to the server.
            if (message.saved) {
                history.push({ role: 'assistant', text: message.text, jobs: message.jobs, see_all_url: message.see_all_url });
                save();
                draw();
            } else {
                typing.classList.remove('animate-pulse', 'text-slate-400');
                typing.innerHTML = render(message.text);
            }
            log.scrollTop = log.scrollHeight;
            busy = false;
        }

        function setOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Đóng trợ lý' : 'Mở trợ lý');
            toggle.querySelector('[data-icon-open]').classList.toggle('hidden', open);
            toggle.querySelector('[data-icon-close]').classList.toggle('hidden', !open);
            if (open) { draw(); input.focus(); }
        }

        toggle.addEventListener('click', () => setOpen(panel.hidden));
        root.querySelector('[data-chat-close]').addEventListener('click', () => setOpen(false));
        root.querySelector('[data-chat-reset]').addEventListener('click', () => { history = []; save(); draw(); input.focus(); });
        suggestions.addEventListener('click', (event) => {
            const button = event.target.closest('[data-chat-suggestion]');
            if (button) send(button.textContent);
        });
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const text = input.value;
            input.value = '';
            input.style.height = '';
            send(text);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                form.requestSubmit();
            }
        });
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 112) + 'px';
        });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !panel.hidden) setOpen(false); });
    })();
</script>
