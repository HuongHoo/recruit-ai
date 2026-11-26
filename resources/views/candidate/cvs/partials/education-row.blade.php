{{-- One education card in the CV builder. $i is the row index ("__i__" in the JS template). --}}
<div class="rounded-xl border border-slate-200 p-4" data-row>
    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm font-semibold text-slate-700">Học vấn <span data-row-number></span> <span class="font-normal text-slate-500" data-row-heading>{{ $row['school'] ?? '' }}</span></p>
        <button type="button" class="text-sm text-red-600 hover:text-red-700" data-remove-row>Xoá</button>
    </div>
    @if (! empty($row['id']))
        <input type="hidden" name="educations[{{ $i }}][id]" value="{{ $row['id'] }}">
    @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="form-label">Tên trường <span class="text-red-500">*</span></label>
            <input name="educations[{{ $i }}][school]" value="{{ $row['school'] ?? '' }}" maxlength="255" class="form-input" data-heading-source>
            @error("educations.$i.school") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Chuyên ngành</label>
            <input name="educations[{{ $i }}][major]" value="{{ $row['major'] ?? '' }}" maxlength="255" class="form-input">
            @error("educations.$i.major") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Bằng cấp</label>
            <select name="educations[{{ $i }}][degree]" class="form-input">
                <option value="">-- Chọn --</option>
                @foreach (config('recruitment.degrees') as $degree)
                    <option value="{{ $degree }}" @selected(($row['degree'] ?? null) === $degree)>{{ $degree }}</option>
                @endforeach
            </select>
            @error("educations.$i.degree") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Bắt đầu <span class="text-red-500">*</span></label>
            <input type="month" name="educations[{{ $i }}][start_date]" value="{{ $row['start_date'] ?? '' }}" class="form-input">
            @error("educations.$i.start_date") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="form-label">Kết thúc</label>
            <input type="month" name="educations[{{ $i }}][end_date]" value="{{ $row['end_date'] ?? '' }}" class="form-input">
            @error("educations.$i.end_date") <p class="form-error">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label class="form-label">Mô tả</label>
            <textarea name="educations[{{ $i }}][description]" rows="2" maxlength="2000" class="form-input" placeholder="VD: Tốt nghiệp loại Giỏi, GPA 3.5/4">{{ $row['description'] ?? '' }}</textarea>
        </div>
    </div>
</div>
