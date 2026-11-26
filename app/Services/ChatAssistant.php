<?php

namespace App\Services;

use App\Enums\JobType;
use App\Models\JobPost;
use Illuminate\Support\Str;

/**
 * The site's chat assistant. One Gemini call turns the conversation into a reply plus, when the
 * person is looking for work, a job filter; the jobs themselves always come from the database, so
 * the assistant can never list a job that does not exist.
 */
class ChatAssistant
{
    public const MAX_JOBS = 5;

    /** Leaves room for the server's count sentence within the 1000-character history limit. */
    public const MAX_REPLY_CHARS = 900;

    private const NO_JOBS = 'Hiện chưa có việc phù hợp, bạn thử nới điều kiện (địa điểm, ngành, mức lương) nhé.';

    public function __construct(private GeminiClient $gemini) {}

    /**
     * @param  list<array{role: string, text: string}>  $messages  oldest first, last one from the user
     * @return array{reply: string, jobs: list<array{title: string, company: string, salary: string, location: string, url: string}>, total: int, see_all_url: ?string}
     *
     * @throws AiServiceException
     */
    public function reply(array $messages): array
    {
        $data = $this->gemini->generateJson($this->instructions(), [['text' => $this->transcript($messages)]], $this->schema(), 0.4, (int) config('services.gemini.chat_timeout'));

        // Capped so the reply still fits the 1000-character limit when the widget sends it back as history.
        $reply = Str::limit(trim((string) ($data['reply'] ?? '')), self::MAX_REPLY_CHARS, '…');
        $raw = is_array($data['job_search'] ?? null) ? array_filter($data['job_search'], fn ($value) => filled($value)) : [];
        $search = $raw !== [] ? $this->cleanSearch($raw) : null;

        if ($search === null) {
            return ['reply' => $reply !== '' ? $reply : 'Mình chưa hiểu ý bạn, bạn nói rõ hơn được không?', 'jobs' => [], 'total' => 0, 'see_all_url' => null];
        }

        // Gemini asked for a search, but nothing it asked for exists on this site (e.g. "việc ở Paris").
        if (array_filter($search, fn ($value) => $value !== null) === []) {
            return ['reply' => trim($reply."\n\n".self::NO_JOBS), 'jobs' => [], 'total' => 0, 'see_all_url' => route('jobs.index')];
        }

        $query = JobPost::query()->visible()->filter($search)->minSalary($search['min_salary']);
        $total = (clone $query)->count();
        $jobs = $query->promotedFirst()->limit(self::MAX_JOBS)->get()->map(fn (JobPost $job) => [
            'title' => $job->title,
            'company' => $job->company_name,
            'salary' => $job->salaryLabel(),
            'location' => $job->location,
            'url' => route('jobs.show', $job),
        ])->all();

        $reply .= $total > 0
            ? "\n\nTìm thấy {$total} việc phù hợp."
            : "\n\n".self::NO_JOBS;

        unset($search['min_salary']); // The job search page has no salary filter yet.

        return [
            'reply' => trim($reply),
            'jobs' => $jobs,
            'total' => $total,
            'see_all_url' => route('jobs.index', array_filter($search)),
        ];
    }

    /**
     * Keeps only filter values the job search understands; Gemini may still invent one.
     *
     * @param  array<string, mixed>  $raw
     * @return array{keyword: ?string, location: ?string, category: ?string, job_type: ?string, min_salary: ?int}
     */
    private function cleanSearch(array $raw): array
    {
        $pick = fn (string $key, array $allowed): ?string => in_array($raw[$key] ?? null, $allowed, true) ? $raw[$key] : null;
        $keyword = trim((string) ($raw['keyword'] ?? ''));
        $salary = filter_var($raw['min_salary'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);

        return [
            'keyword' => $keyword !== '' ? mb_substr($keyword, 0, 100) : null,
            'location' => $pick('location', config('recruitment.locations')),
            'category' => $pick('category', config('recruitment.job_categories')),
            'job_type' => $pick('job_type', array_column(JobType::cases(), 'value')),
            'min_salary' => $salary === false ? null : $salary,
        ];
    }

    /**
     * @param  list<array{role: string, text: string}>  $messages
     */
    private function transcript(array $messages): string
    {
        $lines = array_map(
            fn (array $message) => ($message['role'] === 'assistant' ? 'Trợ lý' : 'Người dùng').': '.$message['text'],
            $messages,
        );

        return "=== CUỘC TRÒ CHUYỆN (cũ → mới) ===\n".implode("\n\n", $lines)."\n\n=== Hãy trả lời tin nhắn cuối cùng của Người dùng. ===";
    }

    private function instructions(): string
    {
        $app = config('app.name');
        $locations = implode(', ', config('recruitment.locations'));
        $categories = implode(', ', config('recruitment.job_categories'));
        $jobTypes = collect(JobType::cases())->map(fn (JobType $type) => "{$type->value} ({$type->label()})")->implode(', ');

        $siteMap = collect([
            'Tìm việc làm (lọc theo từ khoá, ngành, địa điểm, loại hình)' => route('jobs.index'),
            'Tạo CV online' => route('cv-builder'),
            'Mẫu CV' => route('cv-templates.index'),
            'Video CV (quay video giới thiệu bản thân, chia sẻ bằng link)' => route('video-cv'),
            'Luyện phỏng vấn với AI (ứng viên chọn một tin tuyển dụng, AI hỏi và chấm điểm)' => route('candidate.mock-interviews.index'),
            'Cộng đồng (góp ý, chia sẻ kinh nghiệm, hỏi đáp)' => route('community.index'),
            'Bài viết & sự kiện' => route('posts.index'),
            'Đăng ký tài khoản ứng viên' => route('register'),
            'Đăng ký nhà tuyển dụng (sau đó xác thực công ty, đăng tin, mua gói đẩy tin)' => route('register', ['role' => 'employer']),
            'Đăng nhập' => route('login'),
        ])->map(fn (string $url, string $label) => "- {$label}: {$url}")->implode("\n");

        return <<<PROMPT
        Bạn là trợ lý ảo của {$app}, website tuyển dụng tại Việt Nam. Luôn trả lời bằng tiếng Việt, thân thiện, ngắn gọn (tối đa khoảng 120 từ), xưng "mình" và gọi người dùng là "bạn".

        Phạm vi: tìm việc làm, cách dùng các tính năng của website, và tư vấn nghề nghiệp (viết CV, phỏng vấn, deal lương, định hướng). Nếu người dùng hỏi chuyện ngoài phạm vi này, lịch sự từ chối và gợi ý họ hỏi về việc làm.

        Tìm việc: nếu người dùng muốn tìm hoặc xem việc làm (kể cả câu hỏi nối tiếp như "còn lương cao hơn thì sao?"), điền job_search dựa trên toàn bộ cuộc trò chuyện; ngược lại để job_search là null.
        - location chỉ được là một trong: {$locations}. Quy đổi cách gọi khác (TP.HCM, HCM, Sài Gòn → "Hồ Chí Minh"; HN → "Hà Nội"; remote → "Làm việc từ xa").
        - category chỉ được là một trong: {$categories}.
        - job_type chỉ được là một trong: {$jobTypes}.
        - keyword: từ khoá ngắn về vị trí/kỹ năng (ví dụ "PHP", "kế toán"), không lặp lại địa điểm hay ngành đã điền.
        - min_salary: mức lương tối thiểu theo triệu VND/tháng (ví dụ "trên 20 triệu" → 20).
        - Hệ thống sẽ tự gắn danh sách việc làm thật và số lượng ngay sau câu trả lời của bạn, nên trong reply KHÔNG tự liệt kê tên việc, công ty hay số lượng; chỉ viết một câu dẫn ngắn (ví dụ "Đây là các việc IT ở Hà Nội mình tìm được:").

        Các trang của website (chỉ được dẫn link trong danh sách này, viết dạng Markdown [tên](link)):
        {$siteMap}

        Định dạng reply: văn bản thường, được dùng **in đậm**, gạch đầu dòng "- " và link Markdown. Không dùng tiêu đề, bảng hay HTML.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $nullableString = ['type' => 'STRING', 'nullable' => true];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'reply' => ['type' => 'STRING'],
                'job_search' => [
                    'type' => 'OBJECT',
                    'nullable' => true,
                    'properties' => [
                        'keyword' => $nullableString,
                        'location' => [...$nullableString, 'enum' => config('recruitment.locations')],
                        'category' => [...$nullableString, 'enum' => config('recruitment.job_categories')],
                        'job_type' => [...$nullableString, 'enum' => array_column(JobType::cases(), 'value')],
                        'min_salary' => ['type' => 'INTEGER', 'nullable' => true],
                    ],
                ],
            ],
            'required' => ['reply'],
        ];
    }
}
