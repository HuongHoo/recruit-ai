# Cộng đồng & Trợ lý chat — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm diễn đàn Cộng đồng (bài, ảnh, bình luận 2 cấp, thích, báo cáo, kiểm duyệt) và bong bóng Trợ lý chat dùng Gemini trả việc làm thật.

**Architecture:** MVC Laravel như các module hiện có: migrations + models + Policy + Form Requests + controllers + Blade. Thích/bình luận/chat dùng `fetch` JSON. Trợ lý là service `ChatAssistant` gọi `GeminiClient::generateJson` một lần, rồi query `JobPost`.

**Tech Stack:** Laravel 13, PHP 8.3, MySQL, Blade + Tailwind (Vite), PHPUnit, Gemini.

**Spec:** `docs/superpowers/specs/2026-11-26-community-and-chat-assistant-design.md`

## Global Constraints

- Thư mục không phải git repo → bỏ các bước commit; thay bằng chạy test.
- Model mới tên `CommunityPost`, `CommunityPostImage`, `CommunityComment`, `CommunityReport`; bảng `community_posts`, `community_post_images`, `community_comments`, `community_post_likes`, `community_reports`.
- Route tiền tố `/cong-dong`, tên `community.*`; admin `/admin/cong-dong`, tên `admin.community.*`; chat `POST /tro-ly/chat`, tên `assistant.chat`.
- Giới hạn: tiêu đề ≤150, nội dung bài ≤10000, bình luận ≤2000, lý do báo cáo ≤500; ảnh ≤4, `mimes:jpg,jpeg,png,webp`, `max:2048`.
- Throttle: bài `5,1`, bình luận `20,1`, thích `60,1`, báo cáo `10,1`, chat `10,1`.
- Chat: tối đa 10 tin, mỗi tin ≤1000 ký tự, tin cuối role `user`; trả 5 việc + tổng.
- Mọi text giao diện tiếng Việt. Dùng `#[Fillable]` attribute và Enum như code hiện có. Thông báo `database` trả `{icon,title,message,url}`.
- Chạy test: `php artisan test --filter=<Tên>`; cuối cùng `php artisan test` và `vendor/bin/pint --dirty`.

## Review Focus

- Bài `is_hidden` truy cập trực tiếp bằng URL/bình luận/thích → phải 404 cho người không phải admin (test ở Task 2, 4).
- User `is_active = false` vẫn có session → không được đăng/bình luận (test ở Task 2).
- `parent_id` trỏ tới bình luận của bài khác → 422 (test ở Task 3).
- Nội dung chứa `<script>` → hiển thị escape, cả trong bài và trong tin trả lời của trợ lý (test Task 2; JS widget escape trước khi render Markdown).
- Gemini trả `location`/`category` lạ hoặc JSON thiếu `job_search` → bỏ giá trị lạ, không lỗi 500 (test Task 6).

---

### Task 1: Dữ liệu cộng đồng

**Files:**
- Create: `app/Enums/CommunityTopic.php`, 5 migrations `database/migrations/2026_11_26_00000{1..5}_create_community_*`, `app/Models/CommunityPost.php`, `CommunityPostImage.php`, `CommunityComment.php`, `CommunityReport.php`, factories `CommunityPostFactory`, `CommunityCommentFactory`
- Modify: `app/Models/User.php` (quan hệ `communityPosts()`)
- Test: `tests/Feature/CommunityModelTest.php`

**Interfaces:**
- Produces: `CommunityTopic` (`Feedback='feedback'` "Góp ý ứng dụng", `Experience='experience'` "Chia sẻ kinh nghiệm", `Question='question'` "Hỏi đáp") với `label(): string`.
- `CommunityPost`: fillable `title, topic, body`; casts `topic`→enum, `is_hidden` bool; quan hệ `author()` (BelongsTo User, `user_id`), `images()` (ordered `position`), `comments()`, `rootComments()` (`parent_id` null), `likes()` (BelongsToMany User qua `community_post_likes`), `reports()` (morphMany). Scope `visibleTo(?User $user)`: admin thấy hết, còn lại `is_hidden = false`. Method `isLikedBy(?User): bool`, `excerpt(int $len = 200): string`.
- `CommunityComment`: fillable `body, parent_id, reply_to_user_id`; quan hệ `post()`, `author()`, `parent()`, `replies()` (oldest), `replyTo()` (User), `reports()`.
- `CommunityReport`: fillable `reason`; `reportable()` morphTo, `reporter()` (`user_id`), cast `resolved_at` datetime. Unique `(reportable_type, reportable_id, user_id)`.
- Xoá cascade bằng FK `cascadeOnDelete` (images, comments, likes); report morph xoá trong `CommunityPost::booted` deleting + xoá file ảnh `Storage::disk('public')->deleteDirectory("community/{id}")`.

- [ ] Step 1: Test `test_deleting_post_removes_comments_likes_reports_and_image_files` (Storage::fake, tạo bài + ảnh file + comment + like + report → delete → các bảng rỗng, `Storage::disk('public')->assertMissing(...)`) và `test_visible_to_hides_hidden_posts_except_for_admin`.
- [ ] Step 2: Chạy `php artisan test --filter=CommunityModelTest` → FAIL (class không tồn tại).
- [ ] Step 3: Viết enum, migrations, models, factories.
- [ ] Step 4: Chạy lại → PASS.

### Task 2: Bài viết cộng đồng (xem, tạo, sửa, xoá, ảnh) + header

**Files:**
- Create: `app/Http/Controllers/CommunityPostController.php`, `app/Http/Requests/Community/StorePostRequest.php`, `UpdatePostRequest.php`, `app/Policies/CommunityPostPolicy.php`, views `resources/views/community/index.blade.php`, `show.blade.php`, `form.blade.php`, `_post-card.blade.php`, `_author.blade.php` (tên + nhãn `role->label()`)
- Modify: `routes/web.php`, `resources/views/partials/site-header.blade.php` (`$mainNav` thêm `['Cộng đồng', route('community.index'), ['community.*']]`)
- Test: `tests/Feature/CommunityPostTest.php`

**Interfaces:**
- Routes: `community.index` GET `/cong-dong` (`?topic=`, `?q=`, paginate 10); `community.create` GET `/cong-dong/tao`; `community.store` POST; `community.show` GET `/cong-dong/{post}`; `community.edit` GET `/cong-dong/{post}/sua`; `community.update` PUT; `community.destroy` DELETE. Route-model binding `{post}` → `CommunityPost`; route tạo phải khai báo trước `{post}`.
- Policy: `create(User)` = `is_active`; `update(User, post)` = tác giả; `delete` = tác giả hoặc admin. Đăng ký nếu dự án không auto-discover (kiểm `AppServiceProvider`).
- Update request: `remove_images[]` (id ảnh thuộc bài) + `images[]` mới, tổng sau cùng ≤4 (lỗi trên `images`).
- Ảnh lưu `$file->store("community/{$post->id}", 'public')`, `position` tăng dần.

- [ ] Step 1: Tests: `test_guest_can_read_list_and_post_but_not_create` (create → redirect login); `test_member_creates_post_with_images` (2 ảnh fake → 2 bản ghi, file tồn tại, redirect `community.show`); `test_more_than_four_images_or_wrong_type_is_rejected` (5 ảnh → lỗi `images`; `doc.pdf` → lỗi `images.0`); `test_inactive_user_cannot_create`; `test_only_author_can_update_and_author_or_admin_can_delete` (người khác → 403); `test_update_removes_and_adds_images_within_limit`; `test_hidden_post_is_404_for_non_admin_and_visible_to_admin`; `test_filter_by_topic_and_search`; `test_body_is_escaped` (`<script>alert(1)</script>` → `assertDontSee('<script>alert(1)', false)`); `test_header_shows_community_link`.
- [ ] Step 2: Chạy `--filter=CommunityPostTest` → FAIL.
- [ ] Step 3: Implement routes, policy, requests, controller, views (style theo `resources/views/posts/*` và `jobs/*`; nội dung `nl2br(e(...))`; lightbox JS nhỏ inline trong `show`).
- [ ] Step 4: Chạy → PASS.

### Task 3: Bình luận & trả lời + thông báo

**Files:**
- Create: `app/Http/Controllers/CommunityCommentController.php`, `app/Http/Requests/Community/CommentRequest.php`, `app/Policies/CommunityCommentPolicy.php`, `app/Notifications/CommunityCommentCreated.php`, `CommunityReplyCreated.php`, view `resources/views/community/_comment.blade.php`
- Modify: `routes/web.php`, `resources/views/community/show.blade.php`
- Test: `tests/Feature/CommunityCommentTest.php`

**Interfaces:**
- Routes (auth, active): `community.comments.store` POST `/cong-dong/{post}/binh-luan` (`body`, `parent_id?`); `community.comments.update` PUT `/binh-luan/{comment}`; `community.comments.destroy` DELETE `/binh-luan/{comment}`. Trả redirect `community.show#comment-{id}` (form thường); nếu `expectsJson` trả `{html}` của `_comment` để chèn không reload.
- Làm phẳng: nếu `parent_id` là một reply → `parent_id` := reply.parent_id, `reply_to_user_id` := reply.user_id. Nếu là bình luận gốc → `reply_to_user_id` := null (không hiện @). `parent_id` phải thuộc cùng bài, khác → 422.
- Bài ẩn → 404 khi bình luận. Cập nhật `comments_count` (increment/decrement, kể cả khi xoá gốc kéo theo replies).
- Thông báo `database`: `CommunityCommentCreated` (bình luận gốc → tác giả bài), `CommunityReplyCreated` (reply → tác giả bình luận gốc + `replyTo`); bỏ trùng, bỏ chính người viết. Payload `icon: 'chat'`, url `route('community.show', $post).'#comment-'.$id`. Kiểm `partials/notification-bell` hỗ trợ icon đó, nếu không dùng icon có sẵn.

- [ ] Step 1: Tests: `test_comment_notifies_post_author_but_not_self`; `test_reply_to_reply_is_flattened_with_mention`; `test_reply_notifies_root_author_and_mentioned_user_once`; `test_parent_from_other_post_is_rejected`; `test_cannot_comment_on_hidden_post`; `test_deleting_root_comment_removes_replies_and_updates_count`; `test_only_author_or_admin_can_edit_delete_comment`; `test_guest_cannot_comment`.
- [ ] Step 2: Chạy `--filter=CommunityCommentTest` → FAIL.
- [ ] Step 3: Implement; giao diện kiểu Facebook 2 cấp theo spec, nút "Trả lời" mở form inline.
- [ ] Step 4: Chạy → PASS.

### Task 4: Thích & báo cáo

**Files:**
- Create: `app/Http/Controllers/CommunityLikeController.php`, `CommunityReportController.php`
- Modify: `routes/web.php`, `community/show.blade.php`, `_post-card.blade.php`, `_comment.blade.php` (nút báo cáo)
- Test: `tests/Feature/CommunityLikeReportTest.php`

**Interfaces:**
- `community.like` POST `/cong-dong/{post}/thich` → JSON `{liked: bool, likes_count: int}`, toggle, giữ `likes_count` đồng bộ.
- `community.report` POST `/cong-dong/bao-cao` (`type: post|comment`, `id`, `reason` required ≤500) → báo cáo trùng thì không tạo thêm, vẫn báo "Đã gửi báo cáo".

- [ ] Step 1: Tests: `test_like_toggles_and_counts`; `test_cannot_like_hidden_post` (404); `test_report_post_and_comment_once_per_user`; `test_guest_cannot_like_or_report`.
- [ ] Step 2: FAIL → Step 3: implement → Step 4: PASS.

### Task 5: Admin kiểm duyệt

**Files:**
- Create: `app/Http/Controllers/Admin/CommunityController.php`, views `resources/views/admin/community/index.blade.php`, `reports.blade.php`
- Modify: `routes/web.php` (nhóm admin), `resources/views/layouts/app.blade.php:111` (thêm mục `['label' => 'Cộng đồng', 'icon' => 'chat'|icon có sẵn, 'route' => 'admin.community.index', 'active' => ['admin.community.*'], 'badge' => số report chưa xử lý]`)
- Test: `tests/Feature/AdminCommunityTest.php`

**Interfaces:**
- `admin.community.index` GET (`?filter=all|reported|hidden`, kèm `withCount` report chưa xử lý); `admin.community.reports` GET; `admin.community.toggle` PATCH `/admin/cong-dong/{post}/an-hien`; `admin.community.destroy` DELETE `/admin/cong-dong/{post}`; `admin.community.comments.destroy` DELETE `/admin/cong-dong/binh-luan/{comment}`; `admin.community.reports.resolve` PATCH `/admin/cong-dong/bao-cao/{report}`.

- [ ] Step 1: Tests: `test_only_admin_can_access`; `test_admin_hides_and_unhides_post`; `test_reported_filter_lists_only_posts_with_open_reports`; `test_admin_deletes_comment_and_resolves_report`.
- [ ] Step 2: FAIL → Step 3: implement → Step 4: PASS.

### Task 6: Service ChatAssistant

**Files:**
- Create: `app/Services/ChatAssistant.php`
- Modify: `app/Models/JobPost.php` (scope `minSalary(Builder, ?int)`: `salary_max >= m OR salary_min >= m`)
- Test: `tests/Feature/ChatAssistantTest.php`

**Interfaces:**
- Consumes: `GeminiClient::generateJson(string $instructions, array $parts, array $schema, float $temperature)`.
- Produces: `ChatAssistant::reply(array $messages): array` với `$messages` = `list<array{role:'user'|'assistant', text:string}>`; trả `array{reply: string, jobs: list<array{title,company,salary,location,url}>, total: int, see_all_url: ?string}`. Ném `AiServiceException` khi Gemini lỗi.
- Instructions: như spec §6 (vai trò, phạm vi, không tự liệt kê việc, bản đồ tính năng với URL từ `route()`: `jobs.index`, `cv-builder`, `cv-templates.index`, `video-cv`, `candidate.mock-interviews.index`, `community.index`, `register`, `register` với `role=employer`, `posts.index`; danh sách location/category/job_type hợp lệ).
- Schema: `reply` string required; `job_search` object nullable: `keyword` string nullable, `location` enum `config('recruitment.locations')` nullable, `category` enum `config('recruitment.job_categories')` nullable, `job_type` enum `JobType` values nullable, `min_salary` integer nullable.
- Câu thêm của server: có kết quả → `"\n\nTìm thấy {N} việc phù hợp."`; không có → `"\n\nHiện chưa có việc phù hợp, bạn thử nới điều kiện (địa điểm, ngành, mức lương) nhé."`. `see_all_url` = `route('jobs.index', filters khác null trừ min_salary)` khi có `job_search`, ngược lại null.

- [ ] Step 1: Tests (dùng `Http::fake` endpoint Gemini trả `candidates[0].content.parts[0].text` = JSON; xem định dạng `decode()` trong `GeminiClient` và test `CvMatchAnalysisTest` để fake đúng): `test_job_search_returns_real_visible_jobs_only` (2 việc HCM mở, 1 HCM đóng, 1 hết hạn, 1 Hà Nội → total 2, url `jobs.show`); `test_min_salary_filter`; `test_unknown_enum_values_are_ignored` (location "Sài Gòn" → bỏ, vẫn chạy); `test_plain_answer_has_no_jobs` (`job_search` null → jobs [], see_all_url null); `test_conversation_history_is_sent_to_gemini` (`Http::assertSent` body chứa tin trước).
- [ ] Step 2: FAIL → Step 3: implement → Step 4: PASS.

### Task 7: Endpoint chat + bong bóng chat

**Files:**
- Create: `app/Http/Controllers/ChatAssistantController.php` (invokable), `app/Http/Requests/ChatAssistantRequest.php`, `resources/views/partials/chat-assistant.blade.php` (HTML + `<script>` inline, không thêm package)
- Modify: `routes/web.php`, `resources/views/layouts/public.blade.php` (include trước `</body>`); cũng include trong layout candidate/employer nếu chúng dùng `site-header` (không trong admin)
- Test: `tests/Feature/ChatAssistantEndpointTest.php`

**Interfaces:**
- `assistant.chat` POST `/tro-ly/chat`, `throttle:10,1`, mở cho khách. Validate `messages` array 1..10, `messages.*.role` in user,assistant, `messages.*.text` string 1..1000, phần tử cuối role user.
- `AiServiceException` → 503 `{reply: 'Trợ lý đang bận, bạn thử lại sau ít phút nhé.', jobs: [], total: 0, see_all_url: null}`.
- Widget: theo spec §6 (nút góc phải, khung 380×560 desktop / toàn màn hình mobile, 3 gợi ý, "đang trả lời…", "Cuộc trò chuyện mới", `localStorage` key `recruitment-chat-history` bọc try/catch, gửi 10 tin cuối, thẻ việc, "Xem tất cả", 429 → "Bạn gửi hơi nhanh, đợi một chút nhé."). Render: escape HTML trước rồi mới áp **đậm**, xuống dòng, gạch đầu dòng, link chỉ khi `href` bắt đầu bằng `APP_URL` hoặc `/`.

- [ ] Step 1: Tests: `test_guest_can_chat_and_gets_jobs` (fake Gemini); `test_validation_rejects_long_or_bad_messages` (1001 ký tự, 11 tin, tin cuối assistant → 422); `test_gemini_failure_returns_friendly_503`; `test_widget_is_on_public_pages_not_admin` (`home` assertSee `id="chat-assistant"`, admin dashboard assertDontSee).
- [ ] Step 2: FAIL → Step 3: implement → Step 4: PASS.
- [ ] Step 5: Chạy thử thật bằng `php artisan serve` + `npm run build`, hỏi "tìm việc ở thành phố hồ chí minh" trên trình duyệt, kiểm tra thẻ việc hiện ra.

### Task 8: Dữ liệu demo & kiểm tra tổng

**Files:**
- Create: `database/seeders/CommunitySeeder.php` (6–8 bài tiếng Việt đủ 3 chủ đề, vài bình luận + reply, vài like, dùng user có sẵn)
- Modify: `database/seeders/DatabaseSeeder.php` (gọi seeder)

- [ ] Step 1: `php artisan migrate` rồi `php artisan db:seed --class=CommunitySeeder` → không lỗi.
- [ ] Step 2: `php artisan test` → tất cả PASS; `vendor/bin/pint --dirty` → sạch.
