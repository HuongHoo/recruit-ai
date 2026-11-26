{{-- Applicant list. Expects $applications; $showJob (bool) adds the job title column. --}}
@if ($applications->isEmpty())
    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">Chưa có hồ sơ ứng tuyển nào.</div>
@else
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Ứng viên</th>
                    @if ($showJob ?? false) <th class="px-5 py-3">Vị trí</th> @endif
                    <th class="px-5 py-3">Ngày nộp</th>
                    <th class="px-5 py-3">Trạng thái</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($applications as $application)
                    <tr @class(['bg-amber-50/40' => $application->status->value === 'submitted'])>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-900">{{ $application->candidate->name }}</p>
                            <p class="text-xs text-slate-500">{{ $application->candidate->email }}</p>
                        </td>
                        @if ($showJob ?? false)
                            <td class="px-5 py-4 text-slate-700">{{ $application->jobPost->title }}</td>
                        @endif
                        <td class="px-5 py-4 text-slate-600">{{ $application->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-4"><span class="{{ $application->status->badgeClasses() }} rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $application->status->label() }}</span></td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('employer.applications.show', $application) }}" class="font-semibold text-teal-700 hover:text-teal-800">Xem hồ sơ &rarr;</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $applications->links() }}</div>
@endif
