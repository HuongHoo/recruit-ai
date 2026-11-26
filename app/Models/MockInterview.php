<?php

namespace App\Models;

use App\Enums\MockInterviewStatus;
use Database\Factories\MockInterviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A practice interview: AI-generated questions for one job, the candidate's (spoken or typed)
 * answers, and the AI's evaluation once finished.
 *
 * @property array<int, array{question: string, focus: string}> $questions
 * @property array<int, string> $answers
 * @property array{headline: string, summary: string, items: array<int, array{score: int, comment: string, suggestion: string}>, strengths: array<int, string>, improvements: array<int, string>}|null $feedback
 */
class MockInterview extends Model
{
    /** @use HasFactory<MockInterviewFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'answers' => 'array',
            'feedback' => 'array',
            'score' => 'integer',
            'status' => MockInterviewStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<JobPost, $this>
     */
    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    /**
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === MockInterviewStatus::Completed;
    }

    public function languageName(): string
    {
        return config("recruitment.mock_interview.languages.{$this->language}.name", $this->language);
    }

    /** BCP-47 tag used by the browser for text-to-speech and speech recognition. */
    public function speechLang(): string
    {
        return config("recruitment.mock_interview.languages.{$this->language}.speech", 'vi-VN');
    }

    /**
     * The animated interviewer (look, name, voice hints) and the lines they say around the questions.
     *
     * @return array<string, mixed>
     */
    public function interviewer(string $gender): array
    {
        $interviewers = config('recruitment.mock_interview.interviewers');
        $gender = array_key_exists($gender, $interviewers) ? $gender : array_key_first($interviewers);
        $script = config("recruitment.mock_interview.script.{$this->language}") ?? config('recruitment.mock_interview.script.vi');

        $fullName = trim((string) $this->user?->name);
        $replace = [
            ':candidate' => $this->language === 'vi' ? Str::afterLast($fullName, ' ') : $fullName,
            ':name' => $interviewers[$gender]['names'][$this->language] ?? $interviewers[$gender]['names']['vi'],
            ':title' => $script['title'],
            ':company' => (string) $this->jobPost?->company_name,
        ];
        $fill = fn (string $line): string => trim(preg_replace('/\s+([,.])/u', '$1', strtr($line, $replace)));

        return [
            'gender' => $gender,
            'name' => $replace[':name'],
            'title' => $script['title'],
            'ttsVoice' => $interviewers[$gender]['tts_voice'] ?? 'Kore',
            'voice' => $interviewers[$gender]['voice'],
            'avoidVoice' => collect($interviewers)->except($gender)->pluck('voice')->flatten()->values()->all(),
            'greeting' => $fill($script['greeting']),
            'resume' => $fill($script['resume']),
            'acks' => array_map($fill, $script['acks']),
            'skip' => $fill($script['skip']),
            'closing' => $fill($script['closing']),
        ];
    }

    /**
     * Text of one spoken line: "greeting", "resume", "skip", "closing", "ack-N" or "question-N".
     *
     * @param  array<string, mixed>  $interviewer  result of interviewer()
     */
    public function speechLine(array $interviewer, string $key): ?string
    {
        if (preg_match('/^(ack|question)-(\d{1,2})$/', $key, $match)) {
            return $match[1] === 'ack'
                ? ($interviewer['acks'][(int) $match[2]] ?? null)
                : ($this->questions[(int) $match[2]]['question'] ?? null);
        }

        return in_array($key, ['greeting', 'resume', 'skip', 'closing'], true) ? $interviewer[$key] : null;
    }

    public function answeredCount(): int
    {
        return collect($this->answers)->filter(fn (?string $answer): bool => filled($answer))->count();
    }

    /**
     * @return array{label: string, classes: string, ring: string}
     */
    public function verdict(): array
    {
        return match (true) {
            $this->score >= 80 => ['label' => 'Rất tốt', 'classes' => 'text-emerald-600', 'ring' => '#10b981'],
            $this->score >= 60 => ['label' => 'Khá tốt', 'classes' => 'text-blue-600', 'ring' => '#2563eb'],
            $this->score >= 40 => ['label' => 'Cần luyện thêm', 'classes' => 'text-amber-600', 'ring' => '#f59e0b'],
            default => ['label' => 'Chưa đạt', 'classes' => 'text-red-600', 'ring' => '#ef4444'],
        };
    }
}
