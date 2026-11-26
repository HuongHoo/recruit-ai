# Cộng đồng & Trợ lý chat — Thiết kế

Ngày: 2026-11-26 · Dự án: Recruitment (Laravel 13, Blade, Tailwind, MySQL, Gemini)

## 1. Mục đích

Đồ án tốt nghiệp, cần hành vi giống sản phẩm thật (không "chế độ test").

- **Cộng đồng**: nơi người dùng góp ý về ứng dụng, chia sẻ kinh nghiệm, hỏi đáp; ai cũng đọc được.
- **Trợ lý chat**: bong bóng chat nổi để khách (kể cả chưa đăng nhập) hỏi tìm việc, hỏi cách dùng web,
  xin tư vấn nghề nghiệp. Tìm việc phải trả về **việc thật trong DB**.

## 2. Mục tiêu / Không làm / Thất bại nếu

**Mục tiêu**
- Diễn đàn dạng bài viết + bình luận, chủ đề, ảnh, thích, trả lời lồng nhau, báo cáo, kiểm duyệt admin.
- Trợ lý chat trả lời tiếng Việt, nhớ ngữ cảnh trong cuộc trò chuyện, hiển thị thẻ việc làm bấm được.

**Không làm (lần này)**
- Duyệt bài trước khi hiển thị; tin nhắn riêng giữa người dùng; theo dõi (follow) người dùng.
- Lưu lịch sử chat trên server; trợ lý thao tác thay người dùng (nộp đơn, lưu việc…).
- Thích bình luận (chỉ thích bài viết). Video trong bài.

**Thất bại nếu (dù chạy được)**
- Trợ lý đưa ra việc làm / link không tồn tại.
- Khách spam làm cạn quota Gemini, hoặc spam bài/bình luận không giới hạn.
- Người dùng sửa/xoá được nội dung của người khác; ảnh tải lên chạy được như file thực thi.
- Bài bị admin ẩn vẫn xem được qua URL.

## 3. Ràng buộc

- Theo đúng phong cách dự án: Controller + Form Request + Blade + Tailwind, Attribute `#[Fillable]`, Enum,
  Feature test PHPUnit trong `tests/Feature`, thông báo qua bảng `notifications` + `partials/notification-bell`.
- Đã có model `Post` (bài viết của admin, route `/bai-viet`) → model mới đặt tên **`CommunityPost`** để không đụng.
- Gemini qua `App\Services\GeminiClient::generateJson()` (đã có fallback model, ném `AiServiceException`).
- Danh sách địa điểm/ngành nằm ở `config/recruitment.php` (`locations`, `job_categories`); `JobPost::filter()`
  so khớp **chính xác** location/category → Gemini phải trả giá trị thuộc danh sách này (dùng `enum` trong schema).

## 4. Các cách đã cân nhắc

**Cộng đồng** — một cách hợp lý: MVC Laravel như phần còn lại; Thích / gửi bình luận bằng `fetch` nhỏ
(không reload). Loại: package forum có sẵn, Livewire (khác stack, khó giải thích khi bảo vệ).

**Trợ lý chat**
1. **Một lần gọi Gemini trả JSON `{reply, job_search|null}`**, server chạy `JobPost` query, tự thêm câu
   "Tìm thấy N việc" + thẻ việc. — **ĐÃ CHỌN**: nhanh, 1 lượt API, dùng lại `generateJson`.
   Đánh đổi: lời Gemini viết trước khi biết kết quả nên không nhận xét từng việc.
2. Function calling (Gemini gọi `search_jobs`, đọc kết quả rồi trả lời) — thông minh hơn nhưng 2 lượt API, phải
   mở rộng `GeminiClient`.
3. Hai lần gọi cố định — tốn 2 lượt cho mọi câu hỏi. Loại.

## 5. Thiết kế — Cộng đồng

### Dữ liệu
- `community_posts`: id, user_id, topic (enum), title (≤150), body (text, ≤10.000 ký tự), is_hidden (bool),
  likes_count, comments_count (đếm sẵn), timestamps, softDeletes không cần (xoá thật).
- `community_post_images`: id, community_post_id, path, position, timestamps. Tối đa 4 ảnh/bài.
- `community_comments`: id, community_post_id, user_id, parent_id (null = bình luận gốc), reply_to_user_id
  (null hoặc người được @), body (≤2.000), timestamps.
  **Chỉ 2 cấp**: trả lời một câu trả lời → `parent_id` = bình luận gốc của nhánh, `reply_to_user_id` = tác giả
  câu đó, hiển thị "@Tên" đầu nội dung.
- `community_post_likes`: community_post_id + user_id (unique), timestamps.
- `community_reports`: id, reportable (morph: bài hoặc bình luận), user_id, reason (≤500), resolved_at, timestamps.
  Mỗi người báo cáo một nội dung tối đa 1 lần.
- Enum `CommunityTopic`: `feedback` "Góp ý ứng dụng", `experience` "Chia sẻ kinh nghiệm", `question` "Hỏi đáp".

### Quyền
- Xem danh sách/chi tiết: mọi người. Bài `is_hidden` → 404 với tất cả trừ admin (admin thấy kèm nhãn "Đã ẩn").
- Đăng bài, bình luận, thích, báo cáo: đăng nhập (ứng viên, nhà tuyển dụng, admin) và `is_active`.
- Sửa/xoá bài & bình luận: tác giả; admin xoá/ẩn được tất cả. Dùng Policy.
- Xoá bình luận gốc → xoá luôn các trả lời của nó. Xoá bài → xoá ảnh (file), bình luận, like, report.

### Trang & route (tiền tố `/cong-dong`, tên `community.*`)
- `GET /cong-dong` — danh sách, lọc theo `?topic=`, tìm `?q=` (tiêu đề + nội dung), sắp mới nhất, phân trang 10.
  Thẻ bài: tiêu đề, chủ đề, tác giả + nhãn vai trò (Ứng viên/Nhà tuyển dụng/Quản trị), thời gian tương đối,
  trích 200 ký tự, ảnh thu nhỏ đầu tiên (+N), số thích, số bình luận.
- `GET /cong-dong/tao`, `POST /cong-dong` — tạo bài (tiêu đề, chủ đề, nội dung, ảnh multiple).
- `GET /cong-dong/{post}` — chi tiết: nội dung (escape, giữ xuống dòng), lưới ảnh bấm xem to (lightbox JS nhỏ),
  nút Thích, Báo cáo, Sửa/Xoá (nếu được phép), danh sách bình luận gốc cũ→mới, trả lời thụt vào bên dưới,
  ô viết bình luận; nút "Trả lời" mở ô nhập ngay dưới bình luận. Khách thấy "Đăng nhập để bình luận".
- `GET /cong-dong/{post}/sua`, `PUT /cong-dong/{post}` — sửa; giữ/xoá từng ảnh cũ, thêm ảnh mới (tổng ≤4).
- `DELETE /cong-dong/{post}`
- `POST /cong-dong/{post}/thich` (toggle, trả JSON `{liked, likes_count}`).
- `POST /cong-dong/{post}/binh-luan` (body, parent_id?) , `PUT|DELETE /binh-luan/{comment}`.
- `POST /cong-dong/bao-cao` (type, id, reason).
- Header (`partials/site-header.blade.php`, `$mainNav`): thêm "Cộng đồng" → `community.index`, active `community.*`.

### Ảnh
- Validate: `image`, `mimes:jpg,jpeg,png,webp`, `max:2048` KB, tối đa 4; lưu disk `public`
  thư mục `community/{post_id}/` với tên ngẫu nhiên (`store()`), hiển thị qua `Storage::url`.
  Cần `php artisan storage:link` (thư mục `public/storage` đã tồn tại).

### Chống spam
- Throttle: đăng bài 5/phút, bình luận 20/phút, thích 60/phút, báo cáo 10/phút.

### Thông báo (Laravel Notification, kênh `database`)
- `CommunityCommentCreated`: báo tác giả bài khi có bình luận gốc mới (không tự báo chính mình).
- `CommunityReplyCreated`: báo tác giả bình luận gốc và người bị @ (loại trùng, loại chính mình).
- Link thông báo tới `community.show#comment-{id}`.

### Admin (`/admin/cong-dong`, tên `admin.community.*`)
- Danh sách bài (lọc: tất cả / bị báo cáo / đã ẩn), số báo cáo chưa xử lý mỗi bài.
- Xem các báo cáo (bài + bình luận) với lý do; hành động: Ẩn/Hiện bài, Xoá bài, Xoá bình luận,
  Đánh dấu đã xử lý. Thêm mục vào menu admin.

## 6. Thiết kế — Trợ lý chat

### Giao diện
- Partial `partials/chat-assistant.blade.php` gắn vào layout công khai và `site-header`-based layouts
  (mọi trang công khai; không hiện trong trang admin). Nút tròn góc dưới phải → khung chat
  (desktop ~380×560, mobile gần toàn màn hình).
- Lời chào + 3 gợi ý bấm nhanh: "Tìm việc IT ở TP.HCM", "Cách tạo Video CV?", "Mẹo phỏng vấn".
- Tin nhắn trợ lý: văn bản (render Markdown đơn giản an toàn: in đậm, xuống dòng, danh sách, link nội bộ —
  escape HTML trước), thẻ việc làm (tiêu đề, công ty, lương `salaryLabel()`, địa điểm → `jobs.show`),
  nút "Xem tất cả" → `jobs.index` với bộ lọc tương ứng. Hiệu ứng "đang trả lời…". Nút "Cuộc trò chuyện mới".
- Lịch sử lưu `localStorage` (bọc try/catch), gửi lên server **10 tin gần nhất**.

### API
- `POST /tro-ly/chat` (tên `assistant.chat`), CSRF như form thường, `throttle:10,1` theo IP (+ user nếu đăng nhập).
- Request: `messages: [{role: user|assistant, text}]`, tối đa 10 mục, mỗi `text` ≤ 1.000 ký tự, mục cuối là `user`.
- Response: `{reply: string, jobs: [{title, company, salary, location, url}], total: int, see_all_url: string|null}`.
- Lỗi Gemini (`AiServiceException`) → 503 với `reply` thân thiện "Trợ lý đang bận…"; 429 → "Bạn gửi hơi nhanh…".

### Service `App\Services\ChatAssistant`
- Gọi `GeminiClient::generateJson($instructions, $parts, $schema, 0.4)`. Hội thoại được gói thành text
  ("Người dùng: … / Trợ lý: …") trong `parts` (giữ nguyên chữ ký `generateJson`).
- **System instructions** gồm: vai trò trợ lý của Recruitment, chỉ trả lời về việc làm/nghề nghiệp/cách dùng web,
  từ chối lịch sự chủ đề khác; tiếng Việt, ngắn gọn; **không tự liệt kê việc làm cụ thể** (server sẽ gắn);
  bản đồ tính năng web kèm URL thật sinh từ `route()` (tìm việc, tạo CV, mẫu CV, Video CV, luyện phỏng vấn AI,
  cộng đồng, đăng ký nhà tuyển dụng, đăng tin, gói dịch vụ…), chỉ được dẫn các URL này;
  danh sách địa điểm, ngành, loại việc hợp lệ.
- **Schema**: `reply` (string, bắt buộc), `job_search` (object, nullable): `keyword` (string|null),
  `location` (enum từ `recruitment.locations`|null), `category` (enum `job_categories`|null),
  `job_type` (enum `JobType` values|null), `min_salary` (int, triệu VND|null).
  Ví dụ "TP.HCM/Sài Gòn/HCM" → "Hồ Chí Minh" do Gemini chuẩn hoá theo enum.
- Nếu có `job_search`: `JobPost::visible()->filter(...)` + nếu `min_salary` thì
  `where(salary_max >= min OR salary_min >= min)` → `promotedFirst()`, lấy 5 việc + `count()` tổng.
  Server thêm vào cuối `reply`: "Tìm thấy N việc phù hợp." hoặc "Hiện chưa có việc phù hợp, bạn thử nới điều kiện…".
  `see_all_url` = `route('jobs.index', filters)` (bỏ `min_salary` vì trang tìm việc chưa hỗ trợ).
- Server kiểm lại giá trị enum (bỏ giá trị lạ) trước khi query.

## 7. Kiểm thử (Feature tests)
- Cộng đồng: khách xem được, không đăng được; đăng bài có ảnh (Storage::fake); >4 ảnh/ảnh sai định dạng bị từ chối;
  không sửa/xoá được bài người khác; bài ẩn trả 404 cho người thường; trả lời câu trả lời được làm phẳng về
  bình luận gốc + `reply_to_user_id`; thông báo gửi đúng người; like toggle & đếm; admin ẩn/xoá.
- Trợ lý: `Http::fake` Gemini → trả về thẻ việc thật đúng bộ lọc, không trả việc đã đóng/hết hạn;
  enum lạ bị bỏ; lỗi Gemini → 503 thân thiện; validate độ dài; throttle.

## 8. Để người xây dựng tự quyết
- Chi tiết giao diện Tailwind (theo style trang `jobs`/`posts` hiện có), lightbox, định dạng thời gian tương đối.
- Seeder/factory dữ liệu mẫu cho cộng đồng (nên có vài bài để demo).
- Thứ tự file/migration, tên class phụ.
