<?php

namespace Database\Seeders;

use App\Enums\CommunityTopic;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo community posts, comments, replies and likes from the demo accounts (runs once).
 */
class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        if (CommunityPost::query()->exists()) {
            return;
        }

        $candidate = User::where('email', 'candidate@example.com')->firstOrFail();
        $employer = User::where('email', 'employer@example.com')->firstOrFail();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $people = User::factory()->candidate()->count(3)->sequence(
            ['name' => 'Nguyễn Minh Anh'], ['name' => 'Trần Quốc Bảo'], ['name' => 'Lê Thu Hà'],
        )->create();

        $posts = [
            [$people[0], CommunityTopic::Experience, 'Luyện phỏng vấn với AI giúp mình đậu vòng 1!',
                "Mình luyện 3 lần với tính năng phỏng vấn AI trước khi đi phỏng vấn vị trí Backend Developer.\n\nAI hỏi khá sát JD, phần nhận xét chỉ ra mình hay trả lời lan man. Lần phỏng vấn thật mình tự tin hơn hẳn và đã qua vòng 1. Mọi người nên thử nhé!",
                [[$employer, 'Chúc mừng bạn! Bên mình cũng đánh giá cao ứng viên trả lời ngắn gọn, có ví dụ cụ thể.', [[$people[0], 'Cảm ơn anh/chị, em sẽ giữ phong độ ở vòng 2 ạ.']]],
                    [$people[1], 'Bạn luyện bằng giọng nói hay gõ chữ vậy?', [[$people[0], 'Mình dùng giọng nói cho giống thật nha.']]]],
                [$candidate, $people[1], $people[2], $employer]],
            [$candidate, CommunityTopic::Feedback, 'Góp ý: nên có bộ lọc theo mức lương',
                "Trang tìm việc rất dễ dùng, nhưng mình mong có thêm bộ lọc theo khoảng lương để đỡ phải mở từng tin.\n\nNgoài ra nếu có chế độ tối (dark mode) thì tuyệt vời.",
                [[$admin, 'Cảm ơn góp ý của bạn! Đội ngũ đã ghi nhận và sẽ bổ sung trong bản cập nhật tới.', []],
                    [$people[2], 'Đồng ý, mình cũng cần lọc lương.', []]],
                [$people[0], $people[2]]],
            [$people[1], CommunityTopic::Question, 'Sinh viên năm cuối nên làm CV một trang hay hai trang?',
                'Mình chưa có nhiều kinh nghiệm, chỉ có một kỳ thực tập và vài dự án ở trường. Nên làm CV một trang hay cố gắng viết dài ra hai trang ạ?',
                [[$employer, 'Một trang là đủ. Nhà tuyển dụng thường đọc CV trong khoảng 30 giây, hãy ưu tiên dự án và kỹ năng liên quan đến vị trí.', [[$people[1], 'Dạ em cảm ơn, em sẽ rút gọn lại.'], [$people[2], 'Bạn thử dùng mẫu CV trên web, khá gọn đó.']]]],
                [$people[0]]],
            [$people[2], CommunityTopic::Experience, 'Kinh nghiệm deal lương lần đầu',
                "Mình vừa nhận offer đầu tiên. Chia sẻ vài điều mình học được:\n\n- Tìm hiểu mặt bằng lương của vị trí trước\n- Đưa ra một khoảng thay vì một con số\n- Hỏi thêm về phúc lợi, không chỉ lương cứng",
                [[$candidate, 'Bài rất hữu ích, cảm ơn bạn!', []]],
                [$candidate, $people[0], $people[1], $employer, $admin]],
            [$employer, CommunityTopic::Experience, 'Nhà tuyển dụng nhìn gì đầu tiên trong một Video CV?',
                'Với Video CV, mình thường xem 30 giây đầu: cách giới thiệu bản thân, sự tự tin và rõ ràng. Ánh sáng và âm thanh tốt là điểm cộng lớn. Hãy trả lời đúng trọng tâm câu hỏi nhé các bạn!',
                [[$people[1], 'Anh/chị có thể gợi ý độ dài lý tưởng cho mỗi câu trả lời không ạ?', [[$employer, 'Khoảng 45–90 giây mỗi câu là vừa đẹp.']]]],
                [$people[1], $people[2]]],
            [$people[0], CommunityTopic::Question, 'Có ai từng ứng tuyển vị trí remote chưa?',
                'Mình đang tìm việc làm từ xa mảng thiết kế. Mọi người có kinh nghiệm gì khi phỏng vấn online không?',
                [], []],
        ];

        foreach ($posts as $index => [$author, $topic, $title, $body, $comments, $likers]) {
            $post = new CommunityPost(['topic' => $topic, 'title' => $title, 'body' => $body]);
            $post->author()->associate($author);
            $post->created_at = now()->subHours((count($posts) - $index) * 7);
            $post->save();

            foreach ($comments as [$commenter, $text, $replies]) {
                $root = $post->comments()->make(['body' => $text]);
                $root->author()->associate($commenter)->save();

                // Like the real flow: a later reply answers the reply before it and mentions its author.
                $previous = null;
                foreach ($replies as [$replier, $replyText]) {
                    $reply = $post->comments()->make([
                        'body' => $replyText,
                        'parent_id' => $root->id,
                        'reply_to_user_id' => $previous?->id,
                    ]);
                    $reply->author()->associate($replier)->save();
                    $previous = $replier;
                }
            }

            $post->likes()->attach(collect($likers)->pluck('id')->unique()->all());
            $post->comments_count = $post->comments()->count();
            $post->likes_count = $post->likes()->count();
            $post->save();
        }
    }
}
