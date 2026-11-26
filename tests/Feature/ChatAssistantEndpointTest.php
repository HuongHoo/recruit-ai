<?php

namespace Tests\Feature;

use App\Models\JobPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAssistantEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gemini.key' => 'test-key']);
    }

    private function fakeGemini(array $payload): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($payload)]]]]],
            ]),
        ]);
    }

    public function test_guest_can_chat_and_gets_jobs(): void
    {
        $job = JobPost::factory()->create(['location' => 'Hồ Chí Minh', 'title' => 'Backend Developer']);
        $this->fakeGemini(['reply' => 'Đây là việc ở TP.HCM:', 'job_search' => ['location' => 'Hồ Chí Minh']]);

        $this->postJson(route('assistant.chat'), ['messages' => [['role' => 'user', 'text' => 'tìm việc ở thành phố hồ chí minh']]])
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('jobs.0.title', 'Backend Developer')
            ->assertJsonPath('jobs.0.url', route('jobs.show', $job))
            ->assertJsonStructure(['reply', 'jobs' => [['title', 'company', 'salary', 'location', 'url']], 'total', 'see_all_url']);
    }

    public function test_validation_rejects_long_or_bad_messages(): void
    {
        $user = ['role' => 'user', 'text' => 'xin chào'];

        $this->postJson(route('assistant.chat'), ['messages' => [['role' => 'user', 'text' => str_repeat('a', 1001)]]])->assertJsonValidationErrors('messages.0.text');
        $this->postJson(route('assistant.chat'), ['messages' => array_fill(0, 11, $user)])->assertJsonValidationErrors('messages');
        $this->postJson(route('assistant.chat'), ['messages' => [$user, ['role' => 'assistant', 'text' => 'chào']]])->assertJsonValidationErrors('messages');
        $this->postJson(route('assistant.chat'), ['messages' => [['role' => 'system', 'text' => 'x']]])->assertJsonValidationErrors('messages.0.role');
        $this->postJson(route('assistant.chat'), ['messages' => []])->assertJsonValidationErrors('messages');
        Http::assertNothingSent();
    }

    public function test_gemini_failure_returns_friendly_503(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);

        $this->postJson(route('assistant.chat'), ['messages' => [['role' => 'user', 'text' => 'xin chào']]])
            ->assertStatus(503)
            ->assertExactJson(['reply' => 'Trợ lý đang bận, bạn thử lại sau ít phút nhé.', 'jobs' => [], 'total' => 0, 'see_all_url' => null]);
    }

    public function test_chat_is_rate_limited(): void
    {
        $this->fakeGemini(['reply' => 'Chào bạn!', 'job_search' => null]);
        $body = ['messages' => [['role' => 'user', 'text' => 'xin chào']]];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('assistant.chat'), $body)->assertOk();
        }
        $this->postJson(route('assistant.chat'), $body)->assertStatus(429);
    }

    public function test_chat_history_is_stored_per_account(): void
    {
        $this->get(route('home'))->assertSee("'recruitment-chat-history:guest'", false);
        $user = User::factory()->candidate()->create();
        $this->actingAs($user)->get(route('home'))->assertSee("'recruitment-chat-history:{$user->id}'", false);
    }

    public function test_widget_is_on_public_pages_not_admin(): void
    {
        $this->get(route('home'))->assertSee('id="chat-assistant"', false);
        $this->get(route('community.index'))->assertSee('id="chat-assistant"', false);
        $this->actingAs(User::factory()->candidate()->create())->get(route('candidate.dashboard'))->assertSee('id="chat-assistant"', false);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertDontSee('id="chat-assistant"', false);
    }
}
