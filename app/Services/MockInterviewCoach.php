<?php

namespace App\Services;

use App\Enums\MockInterviewStatus;
use App\Models\Cv;
use App\Models\JobPost;
use App\Models\MockInterview;
use Illuminate\Support\Facades\Log;

/**
 * Runs AI mock interviews: writes questions tailored to the job and CV, then grades the answers.
 */
class MockInterviewCoach
{
    /** Fewer usable questions than this means the AI output is not worth starting an interview with. */
    private const MIN_QUESTIONS = 3;

    public function __construct(private GeminiClient $gemini, private CvContent $content) {}

    /**
     * Generates the questions and opens a new interview.
     *
     * @throws AiServiceException
     */
    public function start(Cv $cv, JobPost $job, string $language): MockInterview
    {
        $count = (int) config('recruitment.mock_interview.question_count');

        $data = $this->gemini->generateJson(
            $this->questionInstructions($count, $this->languagePrompt($language)),
            [['text' => "=== MÔ TẢ CÔNG VIỆC (JD) ===\n{$this->content->jobText($job)}"], ...$this->content->parts($cv)],
            [
                'type' => 'OBJECT',
                'properties' => [
                    'questions' => ['type' => 'ARRAY', 'items' => [
                        'type' => 'OBJECT',
                        'properties' => ['question' => ['type' => 'STRING'], 'focus' => ['type' => 'STRING']],
                        'required' => ['question', 'focus'],
                    ]],
                ],
                'required' => ['questions'],
            ],
            temperature: 0.7,
        );

        $questions = collect($data['questions'] ?? [])
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['question'] ?? null))
            ->map(fn (array $row): array => [
                'question' => trim((string) $row['question']),
                'focus' => mb_substr(trim((string) ($row['focus'] ?? '')), 0, 60),
            ])
            ->take($count)
            ->values()
            ->all();

        if (count($questions) < self::MIN_QUESTIONS) {
            Log::warning('Gemini mock interview returned too few questions', ['data' => $data]);

            throw new AiServiceException('AI chưa soạn được bộ câu hỏi phù hợp. Vui lòng thử lại.');
        }

        return MockInterview::create([
            'user_id' => $cv->user_id,
            'job_post_id' => $job->id,
            'cv_id' => $cv->id,
            'cv_title' => $cv->title,
            'language' => $language,
            'questions' => $questions,
            'answers' => array_fill(0, count($questions), ''),
            'status' => MockInterviewStatus::InProgress,
            'model' => $this->gemini->model(),
        ]);
    }

    /**
     * Grades every answer and marks the interview completed.
     *
     * @throws AiServiceException
     */
    public function evaluate(MockInterview $interview): MockInterview
    {
        $transcript = collect($interview->questions)->map(function (array $question, int $index) use ($interview): string {
            $answer = trim($interview->answers[$index] ?? '');

            return 'Câu '.($index + 1)." ({$question['focus']}): {$question['question']}\nTrả lời: ".($answer !== '' ? $answer : '[Ứng viên bỏ qua, không trả lời]');
        })->implode("\n\n");

        $data = $this->gemini->generateJson(
            $this->evaluationInstructions(count($interview->questions), $this->languagePrompt($interview->language)),
            [
                ['text' => "=== MÔ TẢ CÔNG VIỆC (JD) ===\n{$this->content->jobText($interview->jobPost)}"],
                ['text' => "=== BUỔI PHỎNG VẤN ===\n{$transcript}"],
            ],
            $this->evaluationSchema(),
        );

        if (! isset($data['score'], $data['items']) || ! is_array($data['items'])) {
            Log::warning('Gemini mock interview evaluation returned unexpected payload', ['data' => $data]);

            throw new AiServiceException('AI trả về kết quả chấm điểm không hợp lệ. Vui lòng thử lại.');
        }

        [$score, $feedback] = $this->normalizeEvaluation($data, $interview);

        $interview->update([
            'score' => $score,
            'feedback' => $feedback,
            'status' => MockInterviewStatus::Completed,
            'completed_at' => now(),
            'model' => $this->gemini->model(),
        ]);

        return $interview;
    }

    /**
     * Keeps one feedback item per question and forces unanswered questions to zero.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function normalizeEvaluation(array $data, MockInterview $interview): array
    {
        $items = collect($interview->questions)->keys()->map(function (int $index) use ($data, $interview): array {
            $row = is_array($data['items'][$index] ?? null) ? $data['items'][$index] : [];
            $answered = filled(trim($interview->answers[$index] ?? ''));

            return [
                'score' => $answered ? max(0, min(10, (int) ($row['score'] ?? 0))) : 0,
                'comment' => (string) ($row['comment'] ?? ($answered ? '' : 'Bạn chưa trả lời câu này.')),
                'suggestion' => (string) ($row['suggestion'] ?? ''),
            ];
        })->all();

        $score = $interview->answeredCount() === 0 ? 0 : max(0, min(100, (int) $data['score']));

        return [$score, [
            'headline' => mb_substr((string) ($data['headline'] ?? ''), 0, 250),
            'summary' => (string) ($data['summary'] ?? ''),
            'items' => $items,
            'strengths' => array_values(array_map('strval', (array) ($data['strengths'] ?? []))),
            'improvements' => array_values(array_map('strval', (array) ($data['improvements'] ?? []))),
        ]];
    }

    private function languagePrompt(string $language): string
    {
        return config("recruitment.mock_interview.languages.{$language}.prompt", 'tiếng Việt');
    }

    private function questionInstructions(int $count, string $language): string
    {
        return <<<PROMPT
        Bạn là nhà tuyển dụng giàu kinh nghiệm đang phỏng vấn ứng viên cho vị trí trong JD.
        Hãy soạn đúng {$count} câu hỏi phỏng vấn, viết bằng {$language}, được cá nhân hóa theo CV của ứng viên.

        Thứ tự gợi ý:
        1. Một câu khởi động (giới thiệu bản thân, gắn với vị trí).
        2. Hai câu về kinh nghiệm/dự án cụ thể trong CV có liên quan tới JD (nêu đúng tên công ty, dự án, kỹ năng có trong CV).
        3. Một đến hai câu kiểm tra chuyên môn theo yêu cầu quan trọng nhất của JD, đặc biệt yêu cầu CV chưa thể hiện rõ.
        4. Một câu tình huống/hành vi (cách xử lý một tình huống thực tế của công việc này).
        5. Câu cuối về động lực, lý do ứng tuyển hoặc định hướng.

        Quy tắc:
        - Viết toàn bộ câu hỏi bằng {$language}, kể cả khi JD hoặc CV viết bằng ngôn ngữ khác. Chỉ giữ nguyên tên riêng
          (công ty, dự án) và thuật ngữ chuyên môn không có cách gọi thông dụng (ví dụ: Laravel, API, KPI).
        - Mỗi câu chỉ hỏi một ý, ngắn gọn (tối đa khoảng 40 từ), tự nhiên như khi nói, vì câu hỏi sẽ được đọc to.
        - Không đánh số, không thêm lời chào hay giải thích ngoài câu hỏi.
        - "focus" là nhãn ngắn bằng tiếng Việt mô tả câu hỏi kiểm tra điều gì (ví dụ: "Giới thiệu bản thân", "Kinh nghiệm Laravel", "Xử lý tình huống").
        PROMPT;
    }

    private function evaluationInstructions(int $count, string $language): string
    {
        return <<<PROMPT
        Bạn là chuyên gia tuyển dụng, chấm buổi phỏng vấn thử của ứng viên cho vị trí trong JD.
        Câu trả lời được chuyển từ giọng nói sang chữ nên có thể thiếu dấu câu hoặc sai chính tả: không trừ điểm vì lỗi đó, hãy chấm nội dung.

        Quy tắc:
        - "items": đúng {$count} phần tử, theo đúng thứ tự câu hỏi.
          + "score": 0-10 cho câu đó (nội dung đúng trọng tâm, có ví dụ cụ thể, cấu trúc rõ ràng, liên hệ với yêu cầu JD). Câu bỏ qua = 0.
          + "comment": 1-3 câu nhận xét bằng tiếng Việt: điểm tốt và điểm cần sửa.
          + "suggestion": một câu trả lời mẫu ngắn gọn (3-5 câu) viết bằng {$language}, dựa trên thông tin ứng viên đã nói, không bịa thêm kinh nghiệm.
        - "score": 0-100 cho cả buổi, phản ánh chất lượng chung (không chỉ là trung bình cộng). Bỏ qua nhiều câu thì điểm thấp.
        - "headline": một câu kết luận ngắn bằng tiếng Việt. "summary": 2-3 câu tổng quan bằng tiếng Việt.
        - "strengths": tối đa 4 điểm mạnh. "improvements": tối đa 5 lời khuyên cụ thể để phỏng vấn tốt hơn. Viết bằng tiếng Việt.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluationSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'score' => ['type' => 'INTEGER'],
                'headline' => $string,
                'summary' => $string,
                'items' => ['type' => 'ARRAY', 'items' => [
                    'type' => 'OBJECT',
                    'properties' => ['score' => ['type' => 'INTEGER'], 'comment' => $string, 'suggestion' => $string],
                    'required' => ['score', 'comment', 'suggestion'],
                ]],
                'strengths' => ['type' => 'ARRAY', 'items' => $string],
                'improvements' => ['type' => 'ARRAY', 'items' => $string],
            ],
            'required' => ['score', 'headline', 'summary', 'items', 'strengths', 'improvements'],
        ];
    }
}
