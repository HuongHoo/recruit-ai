{{-- One experience card in the CV builder. $i is the row index ("__i__" in the JS template). --}}
<div class="rounded-xl border border-slate-200 p-4" data-row>
    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm font-semibold text-slate-700">Kinh nghiệm <span data-row-number></span> <span class="font-normal text-slate-500" data-row-heading>{{ $row['position'] ?? '' }}</span></p>
        <button type="button" class="text-sm text-red-600 hover:text-red-700" data-remove-row>Xoá</button>
    </div>
    @if (! empty($row['id']))
        <input type="hidden" name="experiences[{{ $i }}][id]" value="{{ $row['id'] }}">
    @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label">Tên công ty <span class="text-red-500">*</span></label>
            <input name="experiences[{{ $i }}][company]" value="{{ $row['company'] ?? '' }}" maxlength="255" class="form-input">
            @error("experiences.$i.company") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Vị trí <span class="text-red-500">*</span></label>
            <input name="experiences[{{ $i }}][position]" value="{{ $row['position'] ?? '' }}" maxlength="255" class="form-input" data-heading-source>
            @error("experiences.$i.position") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Bắt đầu <span class="text-red-500">*</span></label>
            <input type="month" name="experiences[{{ $i }}][start_date]" value="{{ $row['start_date'] ?? '' }}" class="form-input">
            @error("experiences.$i.start_date") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Kết thúc</label>
            <input type="month" name="experiences[{{ $i }}][end_date]" value="{{ $row['end_date'] ?? '' }}" class="form-input disabled:bg-slate-50" data-end-date @disabled(! empty($row['is_current']))>
            <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="experiences[{{ $i }}][is_current]" value="1" data-is-current @checked(! empty($row['is_current']))> Đang làm việc ở đây
            </label>
            @error("experiences.$i.end_date") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Mô tả công việc</label>
            <textarea name="experiences[{{ $i }}][description]" rows="4" maxlength="3000" class="form-input" placeholder="Mỗi dòng một thành tích, bắt đầu bằng động từ và có số liệu. VD: Tăng doanh số khu vực 25% trong 6 tháng">{{ $row['description'] ?? '' }}</textarea>
            @error("experiences.$i.description") <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
