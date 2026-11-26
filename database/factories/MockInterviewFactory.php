<?php

namespace Database\Factories;

use App\Enums\MockInterviewStatus;
use App\Models\Cv;
use App\Models\JobPost;
use App\Models\MockInterview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MockInterview>
 */
class MockInterviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cv_id' => Cv::factory(),
            'user_id' => fn (array $attributes) => Cv::find($attributes['cv_id'])->user_id,
            'cv_title' => fn (array $attributes) => Cv::find($attributes['cv_id'])->title,
            'job_post_id' => JobPost::factory(),
            'language' => 'vi',
            'questions' => [
                ['question' => 'Hãy giới thiệu ngắn gọn về bản thân bạn.', 'focus' => 'Giới thiệu bản thân'],
                ['question' => 'Vì sao bạn muốn ứng tuyển vị trí này?', 'focus' => 'Động lực'],
            ],
            'answers' => ['', ''],
            'status' => MockInterviewStatus::InProgress,
            'model' => 'gemini-test',
        ];
    }
}
