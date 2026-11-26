<?php

namespace Tests\Feature;

use App\Models\JobPost;
use App\Services\AiServiceException;
use App\Services\ChatAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key']);
    }

    /**
     * @param  array<string, mixed>|null  $search
     */
    private function fakeGemini(string $reply, ?array $search = null): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['reply' => $reply, 'job_search' => $search])]]]]],
            ]),
        ]);
    }

    private function ask(string $text, array $history = []): array
    {
        return app(ChatAssistant::class)->reply([...$history, ['role' => 'user', 'text' => $text]]);
    }

    public function test_job_search_returns_real_visible_jobs_only(): void
    {
        $open = JobPost::factory()->count(2)->create(['location' => 'Hồ Chí Minh']);
        JobPost::factory()->closed()->create(['location' => 'Hồ Chí Minh']);
        JobPost::factory()->expired()->create(['location' => 'Hồ Chí Minh']);
        JobPost::factory()->create(['location' => 'Hà Nội']);
        $this->fakeGemini('Đây là các việc ở TP.HCM:', ['location' => 'Hồ Chí Minh']);

        $result = $this->ask('tìm việc ở thành phố hồ chí minh');

        $this->assertSame(2, $result['total']);
        $this->assertCount(2, $result['jobs']);
        $this->assertEqualsCanonicalizing($open->map(fn ($job) => route('jobs.show', $job))->all(), array_column($result['jobs'], 'url'));
        $this->assertSame('Hồ Chí Minh', $result['jobs'][0]['location']);
        $this->assertStringContainsString('Tìm thấy 2 việc phù hợp.', $result['reply']);
        $this->assertSame(route('jobs.index', ['location' => 'Hồ Chí Minh']), $result['see_all_url']);
    }

    public function test_at_most_five_jobs_are_returned_with_full_total(): void
    {
        JobPost::factory()->count(7)->create(['location' => 'Đà Nẵng']);
        $this->fakeGemini('OK', ['location' => 'Đà Nẵng']);

        $result = $this->ask('việc ở Đà Nẵng');

        $this->assertCount(5, $result['jobs']);
        $this->assertSame(7, $result['total']);
    }

    public function test_min_salary_filter(): void
    {
        $high = JobPost::factory()->create(['salary_min' => 15, 'salary_max' => 25]);
        JobPost::factory()->create(['salary_min' => 8, 'salary_max' => 12]);
        JobPost::factory()->create(['salary_min' => null, 'salary_max' => null]);
        $this->fakeGemini('OK', ['min_salary' => 20]);

        $result = $this->ask('lương trên 20 triệu');

        $this->assertSame(1, $result['total']);
        $this->assertSame(route('jobs.show', $high), $result['jobs'][0]['url']);
        $this->assertSame('15 - 25 triệu', $result['jobs'][0]['salary']);
        $this->assertStringContainsString('chưa lọc theo mức lương', $result['reply']);
    }

    public function test_no_results_suggests_loosening(): void
    {
        $this->fakeGemini('Để mình tìm nhé.', ['location' => 'Cần Thơ']);

        $result = $this->ask('việc ở Cần Thơ');

        $this->assertSame(0, $result['total']);
        $this->assertStringContainsString('Hiện chưa có việc phù hợp', $result['reply']);
    }

    public function test_unknown_enum_values_are_ignored_but_valid_ones_still_filter(): void
    {
        $plain = ['company_name' => 'Công ty ABC', 'description' => 'Mô tả công việc.'];
        JobPost::factory()->count(2)->create(['title' => 'Kế toán tổng hợp', ...$plain]);
        JobPost::factory()->create(['title' => 'PHP Developer', ...$plain]);
        $this->fakeGemini('OK', ['location' => 'Sài Gòn', 'category' => 'Phi hành gia', 'job_type' => 'robot', 'keyword' => 'Kế toán']);

        $result = $this->ask('việc kế toán ở Sài Gòn');

        $this->assertSame(2, $result['total']);
        $this->assertSame(route('jobs.index', ['keyword' => 'Kế toán']), $result['see_all_url']);
    }

    public function test_search_with_only_invalid_values_lists_nothing(): void
    {
        JobPost::factory()->count(3)->create();
        $this->fakeGemini('Để mình tìm nhé.', ['location' => 'Paris', 'keyword' => '  ']);

        $result = $this->ask('việc ở Paris');

        $this->assertSame([], $result['jobs']);
        $this->assertSame(0, $result['total']);
        $this->assertStringContainsString('Hiện chưa có việc phù hợp', $result['reply']);
    }

    public function test_all_null_job_search_is_treated_as_no_search(): void
    {
        JobPost::factory()->count(3)->create();
        $this->fakeGemini('Bạn vào mục Video CV nhé.', ['keyword' => null, 'location' => null, 'category' => null, 'job_type' => null, 'min_salary' => null]);

        $result = $this->ask('Cách tạo Video CV?');

        $this->assertSame('Bạn vào mục Video CV nhé.', $result['reply']);
        $this->assertSame([], $result['jobs']);
        $this->assertNull($result['see_all_url']);
    }

    public function test_long_reply_is_capped_so_it_can_be_sent_back_as_history(): void
    {
        JobPost::factory()->create(['location' => 'Hà Nội']);
        $this->fakeGemini(str_repeat('Mẹo phỏng vấn. ', 120), ['location' => 'Hà Nội']);

        $result = $this->ask('mẹo phỏng vấn và việc ở Hà Nội');

        $this->assertLessThanOrEqual(1000, mb_strlen($result['reply']));
        $this->assertStringEndsWith('Tìm thấy 1 việc phù hợp.', $result['reply']);
    }

    public function test_plain_answer_has_no_jobs(): void
    {
        JobPost::factory()->create();
        $this->fakeGemini('Bạn vào mục **Tạo CV** để bắt đầu.');

        $result = $this->ask('cách tạo CV?');

        $this->assertSame('Bạn vào mục **Tạo CV** để bắt đầu.', $result['reply']);
        $this->assertSame([], $result['jobs']);
        $this->assertSame(0, $result['total']);
        $this->assertNull($result['see_all_url']);
    }

    public function test_conversation_history_and_site_map_are_sent_to_gemini(): void
    {
        $this->fakeGemini('OK');

        $this->ask('còn lương trên 20 triệu thì sao?', [
            ['role' => 'user', 'text' => 'việc IT ở Hà Nội'],
            ['role' => 'assistant', 'text' => 'Có 3 việc IT ở Hà Nội.'],
        ]);

        Http::assertSent(function (Request $request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return str_contains($body, 'việc IT ở Hà Nội')
                && str_contains($body, 'Có 3 việc IT ở Hà Nội.')
                && str_contains($body, 'còn lương trên 20 triệu thì sao?')
                && str_contains($body, route('cv-builder'))
                && str_contains($body, 'Hồ Chí Minh');
        });
    }

    public function test_gemini_failure_throws_ai_exception(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);

        $this->expectException(AiServiceException::class);
        $this->ask('xin chào');
    }
}
