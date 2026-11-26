{{-- Client logic for the CV builder: steps, repeatable rows, skills, live preview and ATS score. --}}
<script>
(() => {
    const form = document.getElementById('cv-builder');
    const $ = (sel, root = form) => root.querySelector(sel);
    const $$ = (sel, root = form) => [...root.querySelectorAll(sel)];
    const value = (name) => (form.elements[name]?.value ?? '').trim();

    /* ---------- Steps ---------- */
    const steps = $$('[data-step]').map((el) => el.dataset.step);
    let current = steps.indexOf(form.dataset.startStep);
    if (current < 0) current = 0;

    const showStep = (index) => {
        current = Math.max(0, Math.min(steps.length - 1, index));
        $$('[data-step]').forEach((el, i) => { el.hidden = i !== current; });
        $$('[data-step-tab]').forEach((tab, i) => tab.setAttribute('aria-current', i === current ? 'step' : 'false'));
        $('[data-prev]').style.visibility = current === 0 ? 'hidden' : 'visible';
        $('[data-next]').hidden = current === steps.length - 1;
        $('[data-submit]').hidden = current !== steps.length - 1;
    };
    $$('[data-step-tab]').forEach((tab, i) => tab.addEventListener('click', () => showStep(i)));
    $('[data-prev]').addEventListener('click', () => showStep(current - 1));
    $('[data-next]').addEventListener('click', () => { showStep(current + 1); window.scrollTo({ top: 0, behavior: 'smooth' }); });

    /* ---------- Repeatable rows ---------- */
    let rowSeq = Date.now();
    const refreshRows = () => {
        $$('[data-list]').forEach((list) => {
            $$('[data-row]', list).forEach((row, i) => {
                $('[data-row-number]', row).textContent = i + 1;
                const source = $('[data-heading-source]', row);
                if (source) $('[data-row-heading]', row).textContent = source.value;
            });
        });
    };
    $$('[data-add]').forEach((button) => button.addEventListener('click', () => {
        const key = button.dataset.add;
        const html = $(`[data-row-template="${key}"]`).innerHTML.replaceAll('__i__', `n${rowSeq++}`);
        $(`[data-list="${key}"]`).insertAdjacentHTML('beforeend', html);
        refreshRows();
        $(`[data-list="${key}"] [data-row]:last-child input`)?.focus();
        changed();
    }));
    form.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-row]');
        if (remove && confirm('Xoá mục này?')) { remove.closest('[data-row]').remove(); refreshRows(); changed(); }
        const removeSkill = event.target.closest('[data-remove-skill]');
        if (removeSkill) { removeSkill.closest('[data-skill]').remove(); changed(); }
    });
    form.addEventListener('change', (event) => {
        if (event.target.matches('[data-is-current]')) {
            const end = $('[data-end-date]', event.target.closest('[data-row]'));
            end.disabled = event.target.checked;
            if (event.target.checked) end.value = '';
        }
    });

    /* ---------- Skills ---------- */
    const skillInput = $('[data-skill-input]');
    const addSkill = (text) => {
        const skill = text.trim().replace(/,$/, '');
        if (!skill) return;
        const exists = $$('input[name="skills[]"]').some((i) => i.value.toLowerCase() === skill.toLowerCase());
        if (exists) return;
        const chip = document.createElement('span');
        chip.className = 'flex items-center gap-1 rounded-full bg-blue-50 py-1 pr-1 pl-3 text-sm text-blue-800';
        chip.dataset.skill = '';
        chip.append(skill);
        const hidden = Object.assign(document.createElement('input'), { type: 'hidden', name: 'skills[]', value: skill });
        const remove = Object.assign(document.createElement('button'), { type: 'button', className: 'grid size-5 place-items-center rounded-full hover:bg-blue-100', innerHTML: '&times;' });
        remove.dataset.removeSkill = '';
        chip.append(hidden, remove);
        skillInput.before(chip);
        changed();
    };
    skillInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ',') { event.preventDefault(); addSkill(skillInput.value); skillInput.value = ''; }
        if (event.key === 'Backspace' && skillInput.value === '') { $$('[data-skill]').pop()?.remove(); changed(); }
    });
    skillInput.addEventListener('blur', () => { addSkill(skillInput.value); skillInput.value = ''; });
    // Pressing Enter in other inputs should not submit the form mid-way.
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.matches('input:not([type=submit])')) event.preventDefault();
    });

    /* ---------- Template picker ---------- */
    const picker = document.getElementById('template-picker');
    const templateInput = $('[data-template-input]');
    const markPicked = () => $$('[data-pick-template]', picker).forEach((b) => b.toggleAttribute('data-selected', b.dataset.pickTemplate === templateInput.value));
    $$('[data-open-picker]').forEach((b) => b.addEventListener('click', () => { markPicked(); picker.showModal(); }));
    $('[data-close-picker]', picker).addEventListener('click', () => picker.close());
    $$('[data-pick-template]', picker).forEach((button) => button.addEventListener('click', () => {
        templateInput.value = button.dataset.pickTemplate;
        $$('[data-template-label]').forEach((el) => { el.textContent = button.dataset.name; });
        picker.close();
        changed();
    }));

    /* ---------- ATS score (client-side estimate) ---------- */
    const atsTemplates = Object.fromEntries($$('[data-pick-template]', picker).map((b) => [b.dataset.pickTemplate, b.dataset.ats === '1']));
    const rows = (key) => $$(`[data-list="${key}"] [data-row]`);
    const checks = () => {
        const exp = rows('experiences');
        const descriptions = exp.map((r) => $('textarea', r).value.trim());
        const skills = $$('input[name="skills[]"]').length;
        const summary = value('objective').length;
        return [
            { label: 'Có họ tên và email', points: 5, ok: value('name') !== '', step: 0 },
            { label: 'Thêm số điện thoại', points: 10, ok: /^[0-9+\s.\-]{8,20}$/.test(value('phone')), step: 0 },
            { label: 'Thêm địa chỉ', points: 5, ok: value('address') !== '', step: 0 },
            { label: 'Ghi rõ vị trí ứng tuyển', points: 10, ok: value('headline') !== '', step: 0 },
            { label: 'Viết tóm tắt từ 150 ký tự', points: 15, ok: summary >= 150, step: 1 },
            { label: 'Có ít nhất 1 kinh nghiệm làm việc', points: 15, ok: exp.length > 0, step: 2 },
            { label: 'Mô tả công việc có ít nhất 2 ý', points: 10, ok: descriptions.some((d) => d.split(/\n+/).filter(Boolean).length >= 2), step: 2 },
            { label: 'Có thông tin học vấn', points: 10, ok: rows('educations').length > 0, step: 3 },
            { label: 'Có từ 5 kỹ năng trở lên', points: 15, ok: skills >= 5, step: 4 },
            { label: 'Dùng mẫu thân thiện ATS', points: 5, ok: atsTemplates[templateInput.value] === true, step: 5 },
        ];
    };
    const renderAts = () => {
        const list = checks();
        const score = list.reduce((sum, c) => sum + (c.ok ? c.points : 0), 0);
        $$('[data-ats-score]').forEach((el) => { el.textContent = score; });
        const [label, color] = score >= 85 ? ['Rất tốt', '#059669'] : score >= 65 ? ['Tốt', '#2563eb'] : score >= 40 ? ['Trung bình', '#d97706'] : ['Cần cải thiện', '#dc2626'];
        $('[data-ats-label]').textContent = label;
        $('[data-ats-ring]').style.background = `conic-gradient(${color} ${score * 3.6}deg, #e2e8f0 0)`;

        const item = (c, done) => {
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between gap-2';
            li.innerHTML = `<span>${done ? '✓' : '•'} </span>`;
            li.firstChild.append(c.label);
            if (!done) {
                const go = Object.assign(document.createElement('button'), { type: 'button', className: 'shrink-0 text-xs font-semibold text-blue-700 hover:underline', textContent: `Thêm ngay +${c.points}` });
                go.addEventListener('click', () => showStep(c.step));
                li.append(go);
            }
            return li;
        };
        $('[data-ats-todo]').replaceChildren(...list.filter((c) => !c.ok).map((c) => item(c, false)));
        $('[data-ats-done]').replaceChildren(...list.filter((c) => c.ok).map((c) => item(c, true)));
        $('[data-ats-done-count]').textContent = list.filter((c) => c.ok).length;

        // Step completion dots + progress line (finish step excluded).
        const done = [
            value('name') !== '' && value('phone') !== '',
            value('objective') !== '',
            rows('experiences').length > 0,
            rows('educations').length > 0,
            $$('input[name="skills[]"]').length > 0,
        ];
        $$('[data-step-dot]').forEach((dot, i) => dot.toggleAttribute('data-done', i < done.length && done[i]));
        const count = done.filter(Boolean).length;
        $('[data-progress]').textContent = `Đã điền ${count}/${done.length} mục` + (count < done.length ? ` · Còn ${done.length - count} mục cần bổ sung` : ' · Sẵn sàng lưu');
        $('[data-count="objective"]').textContent = value('objective').length;
        $('[data-title-label]').textContent = value('title') || 'CV';
    };

    /* ---------- Live preview ---------- */
    const preview = $('[data-preview]');
    const status = $('[data-preview-status]');
    let timer, controller;
    const loadPreview = async () => {
        controller?.abort();
        controller = new AbortController();
        status.textContent = 'Đang cập nhật…';
        try {
            const body = new FormData(form);
            body.delete('_method');
            const response = await fetch(form.dataset.previewUrl, { method: 'POST', body, signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(response.status);
            preview.innerHTML = await response.text();
            const accent = form.elements.accent_color?.value || '#2563eb';
            preview.style.setProperty('--accent', accent);
            status.textContent = 'Đã cập nhật';
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Không tải được bản xem trước';
        }
    };
    function changed() {
        renderAts();
        refreshRows();
        clearTimeout(timer);
        timer = setTimeout(loadPreview, 500);
    }
    form.addEventListener('input', changed);
    form.addEventListener('change', changed);

    showStep(current);
    refreshRows();
    renderAts();
    loadPreview();
})();
</script>
