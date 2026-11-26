<?php

namespace App\Services;

use App\Enums\RequirementMatch;
use App\Models\Cv;
use App\Models\CvMatchAnalysis;
use App\Models\JobPost;
use Illuminate\Support\Facades\Log;

/**
 * Scores how well a CV fits a job description using the Google Gemini API.
 */
class CvMatchAnalyzer
{
    /** Radar-chart axes, always returned in this order. */
    public const CRITERIA = ['Kỹ năng chuyên môn', 'Kinh nghiệm làm việc', 'Học vấn', 'Ngoại ngữ', 'Kỹ năng mềm'];

    public function __construct(private GeminiClient $gemini, private CvContent $content) {}

    /**
     * Returns the stored analysis, or runs a new one when none exists, the CV/JD changed, or $refresh is set.
     *
     * @throws AiServiceException
     */
    public function analyze(Cv $cv, JobPost $job, bool $refresh = false): CvMatchAnalysis
    {
        $hash = $this->sourceHash($cv, $job);
        $existing = CvMatchAnalysis::query()->where('cv_id', $cv->id)->where('job_post_id', $job->id)->first();

        if ($existing && ! $refresh && $existing->source_hash === $hash) {
            return $existing;
        }

        $result = $this->callGemini($this->content->parts($cv), $this->content->jobText($job));

        return CvMatchAnalysis::updateOrCreate(
            ['cv_id' => $cv->id, 'job_post_id' => $job->id],
            [...$result, 'user_id' => $cv->user_id, 'source_hash' => $hash, 'model' => $this->gemini->model()],
        );
    }

    /**
     * Whether a stored analysis no longer reflects the current CV or JD.
     */
    public function isOutdated(CvMatchAnalysis $analysis): bool
    {
        return $analysis->source_hash !== $this->sourceHash($analysis->cv, $analysis->jobPost);
    }

    public function supports(Cv $cv): bool
    {
        return $this->content->supports($cv);
    }

    private function sourceHash(Cv $cv, JobPost $job): string
    {
        return hash('sha256', $this->content->fingerprint($cv).'||'.$this->content->jobText($job));
    }

    /**
     * @param  array<int, array<string, mixed>>  $cvParts
     * @return array{score: int, headline: string, summary: string, criteria: array<int, array{name: string, cv: int, jd: int}>, requirements: array<int, array{requirement: string, status: string, evidence: string}>, strengths: array<int, string>, suggestions: array<int, string>}
     *
     * @throws AiServiceException
     */
    private function callGemini(array $cvParts, string $jobText): array
    {
        $data = $this->gemini->generateJson(
            $this->instructions(),
            [['text' => "=== MÔ TẢ CÔNG VIỆC (JD) ===\n{$jobText}"], ...$cvParts],
            $this->responseSchema(),
        );

        if (! isset($data['score'], $data['requirements']) || ! is_array($data['requirements'])) {
            Log::warning('Gemini CV match returned unexpected payload', ['data' => $data]);

            throw new CvMatchException('AI trả về kết quả không hợp lệ. Vui lòng thử lại.');
        }

        return $this->normalize($data);
    }

    /**
     * Clamps numbers and keeps only known values so the view can trust the stored data.
     *
     * @param  array<string, mixed>  $data
     * @return array{score: int, headline: string, summary: string, criteria: array<int, array{name: string, cv: int, jd: int}>, requirements: array<int, array{requirement: string, status: string, evidence: string}>, strengths: array<int, string>, suggestions: array<int, string>}
     */
    private function normalize(array $data): array
    {
        $clamp = fn (mixed $value, int $max): int => max(0, min($max, (int) $value));
        $criteriaByName = collect($data['criteria'] ?? [])->keyBy('name');

        return [
            'score' => $clamp($data['score'], 100),
            'headline' => mb_substr((string) ($data['headline'] ?? ''), 0, 250),
            'summary' => (string) ($data['summary'] ?? ''),
            'criteria' => array_map(fn (string $name): array => [
                'name' => $name,
                'cv' => $clamp($criteriaByName[$name]['cv'] ?? 0, 5),
                'jd' => $clamp($criteriaByName[$name]['jd'] ?? 0, 5),
            ], self::CRITERIA),
            'requirements' => collect($data['requirements'])->map(fn (array $row): array => [
                'requirement' => (string) ($row['requirement'] ?? ''),
                'status' => RequirementMatch::tryFrom((string) ($row['status'] ?? ''))?->value ?? RequirementMatch::Missing->value,
                'evidence' => (string) ($row['evidence'] ?? ''),
            ])->filter(fn (array $row): bool => $row['requirement'] !== '')->values()->all(),
            'strengths' => array_values(array_map('strval', $data['strengths'] ?? [])),
            'suggestions' => array_values(array_map('strval', $data['suggestions'] ?? [])),
        ];
    }

    private function instructions(): string
    {
        $criteria = implode(', ', self::CRITERIA);

        return <<<PROMPT
        Bạn là chuyên gia tuyển dụng. Hãy đánh giá mức độ phù hợp giữa CV và mô tả công việc (JD), trả lời hoàn toàn bằng tiếng Việt.

        Quy tắc:
        - Chỉ dựa trên nội dung có trong CV và JD, không suy đoán thông tin không có.
        - Tách JD thành 4-10 yêu cầu nổi bật (kỹ năng, kinh nghiệm, học vấn, ngoại ngữ, phẩm chất...). Với từng yêu cầu, chọn status:
          "met" = CV thể hiện rõ đáp ứng; "unclear" = có dấu hiệu liên quan nhưng chưa đủ rõ; "unmet" = CV cho thấy không đáp ứng; "missing" = CV không đề cập.
          "evidence" là 1-2 câu giải thích, trích dẫn chi tiết từ CV nếu có.
        - "criteria": chấm đúng 5 tiêu chí theo thứ tự [{$criteria}]; "jd" = mức JD yêu cầu (0-5), "cv" = mức CV thể hiện (0-5).
        - "score": 0-100, phản ánh tỷ lệ yêu cầu được đáp ứng (yêu cầu quan trọng có trọng số cao hơn). CV thiếu thông tin thì điểm thấp.
        - "headline": một câu kết luận ngắn. "summary": 2-3 câu giải thích.
        - "strengths": tối đa 4 điểm mạnh của CV so với JD. "suggestions": tối đa 5 gợi ý cụ thể để ứng viên cải thiện CV cho vị trí này.
        - Đây là đánh giá nội dung CV so với JD, không phải xác suất được tuyển.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'score' => ['type' => 'INTEGER'],
                'headline' => $string,
                'summary' => $string,
                'criteria' => ['type' => 'ARRAY', 'items' => [
                    'type' => 'OBJECT',
                    'properties' => ['name' => $string, 'cv' => ['type' => 'INTEGER'], 'jd' => ['type' => 'INTEGER']],
                    'required' => ['name', 'cv', 'jd'],
                ]],
                'requirements' => ['type' => 'ARRAY', 'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'requirement' => $string,
                        'status' => ['type' => 'STRING', 'enum' => array_column(RequirementMatch::cases(), 'value')],
                        'evidence' => $string,
                    ],
                    'required' => ['requirement', 'status', 'evidence'],
                ]],
                'strengths' => ['type' => 'ARRAY', 'items' => $string],
                'suggestions' => ['type' => 'ARRAY', 'items' => $string],
            ],
            'required' => ['score', 'headline', 'summary', 'criteria', 'requirements', 'strengths', 'suggestions'],
        ];
    }
}
