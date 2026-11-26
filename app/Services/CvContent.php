<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\JobPost;
use Illuminate\Support\Facades\Storage;

/**
 * Turns a CV and a job post into text/file parts that can be sent to the AI.
 *
 * Online CVs become text built from the candidate profile; uploaded PDFs are sent as the file
 * itself (Gemini reads PDFs natively). Word files are not supported.
 */
class CvContent
{
    public function supports(Cv $cv): bool
    {
        return $cv->isOnline() || $cv->isPdf();
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws AiServiceException
     */
    public function parts(Cv $cv): array
    {
        if ($cv->isOnline()) {
            return [['text' => "=== CV ỨNG VIÊN ===\n".$this->onlineText($cv)]];
        }

        if (! $cv->isPdf()) {
            throw new AiServiceException('AI hiện chỉ đọc được CV trực tuyến hoặc CV dạng PDF. Hãy tải lên bản PDF của CV này.');
        }

        $content = Storage::disk('local')->get($cv->file_path);

        if ($content === null) {
            throw new AiServiceException('Không đọc được file CV. Hãy tải lại CV lên.');
        }

        return [
            ['text' => '=== CV ỨNG VIÊN (file PDF đính kèm) ==='],
            ['inline_data' => ['mime_type' => 'application/pdf', 'data' => base64_encode($content)]],
        ];
    }

    /**
     * A string that changes whenever the CV content changes.
     */
    public function fingerprint(Cv $cv): string
    {
        return $cv->isOnline() ? $this->onlineText($cv) : $cv->file_path.'|'.$cv->file_size;
    }

    public function onlineText(Cv $cv): string
    {
        $user = $cv->user;
        $profile = $user->profile();

        $lines = [
            "Họ tên: {$user->name}",
            'Tiêu đề: '.($profile->headline ?: '—'),
            'Giới thiệu: '.($profile->summary ?: '—'),
            'Mục tiêu nghề nghiệp: '.($cv->objective ?: '—'),
            'Kỹ năng: '.(filled($profile->skills) ? implode(', ', $profile->skills) : '—'),
            'Địa điểm mong muốn: '.(filled($profile->desired_locations) ? implode(', ', $profile->desired_locations) : '—'),
            'Mức lương mong muốn (triệu VNĐ): '.($profile->desired_salary_min ?? '?').' - '.($profile->desired_salary_max ?? '?'),
            '',
            'KINH NGHIỆM LÀM VIỆC:',
        ];

        foreach ($user->experiences()->get() as $experience) {
            $end = $experience->is_current ? 'nay' : $experience->end_date?->format('m/Y');
            $lines[] = "- {$experience->position} tại {$experience->company} ({$experience->start_date->format('m/Y')} - {$end}): {$experience->description}";
        }

        $lines[] = '';
        $lines[] = 'HỌC VẤN:';

        foreach ($user->educations()->get() as $education) {
            $lines[] = "- {$education->degree} {$education->major} — {$education->school} ({$education->start_date->format('Y')} - ".($education->end_date?->format('Y') ?? 'nay')."): {$education->description}";
        }

        return implode("\n", $lines);
    }

    public function jobText(JobPost $job): string
    {
        return implode("\n", [
            "Vị trí: {$job->title}",
            "Công ty: {$job->company_name}",
            "Ngành nghề: {$job->category}",
            "Địa điểm: {$job->location}",
            "Hình thức: {$job->job_type->label()}",
            'Kinh nghiệm yêu cầu: '.($job->experience ?? 'Không yêu cầu'),
            "Mức lương: {$job->salaryLabel()}",
            "Mô tả công việc:\n{$job->description}",
            "Yêu cầu ứng viên:\n".($job->requirements ?: '—'),
            "Quyền lợi:\n".($job->benefits ?: '—'),
        ]);
    }
}
