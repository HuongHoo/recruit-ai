@extends('layouts.app')
@section('title', 'Đổi tên CV')

@section('content')
    <div class="mx-auto max-w-lg">
        <a href="{{ route('candidate.cvs.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Quay lại danh sách CV</a>

        <form method="POST" action="{{ route('candidate.cvs.update', $cv) }}" class="mt-4 space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf
            @method('PUT')
            <p class="text-sm text-slate-500">Tệp gốc: <span class="font-medium text-slate-700">{{ $cv->original_name }}</span> ({{ $cv->fileSizeLabel() }})</p>
            <div>
                <label for="title" class="form-label">Tên CV</label>
                <input id="title" name="title" value="{{ old('title', $cv->title) }}" required class="form-input">
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('candidate.cvs.index') }}" class="btn-secondary">Hủy</a>
                <button type="submit" class="btn-primary">Lưu</button>
            </div>
        </form>
    </div>
@endsection
