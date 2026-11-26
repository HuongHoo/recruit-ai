<?php

namespace Database\Factories;

use App\Enums\JobPostStatus;
use App\Enums\JobType;
use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Generates realistic Vietnamese job posts.
 *
 * @extends Factory<JobPost>
 */
class JobPostFactory extends Factory
{
    /**
     * Sample companies used for demo data.
     *
     * @var array<int, string>
     */
    public const COMPANIES = [
        'Công ty CP Công nghệ Sao Việt',
        'Công ty TNHH Phần mềm Minh An',
        'Tập đoàn Bán lẻ Hòa Phát Xanh',
        'Ngân hàng TMCP Phương Nam',
        'Công ty CP Logistics Đông Á',
        'Công ty TNHH Truyền thông Ánh Dương',
        'Công ty CP Sản xuất Nhựa Tân Tiến',
        'Hệ thống Giáo dục Trí Việt',
    ];

    /**
     * Job templates: [title, category, experience, salary min, salary max, description, requirements, benefits].
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: int, 4: int, 5: string, 6: string, 7: string}>
     */
    private const TEMPLATES = [
        ['Lập trình viên PHP / Laravel', 'IT - Phần mềm', '1 - 2 năm', 15, 25,
            "- Phát triển và bảo trì các hệ thống web sử dụng Laravel.\n- Thiết kế cơ sở dữ liệu, viết API RESTful cho ứng dụng di động.\n- Phối hợp với đội Frontend và QA để hoàn thành sản phẩm đúng tiến độ.",
            "- Tốt nghiệp Cao đẳng/Đại học chuyên ngành CNTT.\n- Có ít nhất 1 năm kinh nghiệm với PHP, Laravel, MySQL.\n- Biết sử dụng Git; ưu tiên ứng viên biết Vue.js hoặc Docker.\n- Đọc hiểu tài liệu kỹ thuật tiếng Anh.",
            "- Lương tháng 13, thưởng dự án.\n- Đóng BHXH đầy đủ theo quy định.\n- Laptop làm việc, 12 ngày phép/năm.\n- Môi trường trẻ trung, được đào tạo công nghệ mới."],
        ['Lập trình viên Frontend ReactJS', 'IT - Phần mềm', '2 - 5 năm', 20, 35,
            "- Xây dựng giao diện web bằng ReactJS, TypeScript.\n- Tối ưu hiệu năng và trải nghiệm người dùng trên nhiều thiết bị.\n- Review code, hướng dẫn thành viên mới.",
            "- Tối thiểu 2 năm kinh nghiệm ReactJS.\n- Thành thạo HTML, CSS, JavaScript ES6+.\n- Có kinh nghiệm với Redux hoặc React Query.\n- Tiếng Anh đọc hiểu tốt.",
            "- Thu nhập cạnh tranh, review lương 2 lần/năm.\n- Bảo hiểm sức khỏe cao cấp.\n- Làm việc hybrid 2 ngày/tuần tại nhà."],
        ['Kiểm thử phần mềm (Tester / QA)', 'IT - Phần mềm', 'Dưới 1 năm', 10, 16,
            "- Viết test case, thực hiện kiểm thử chức năng cho ứng dụng web và mobile.\n- Ghi nhận, theo dõi lỗi trên Jira.\n- Phối hợp với lập trình viên để tái hiện và xác nhận lỗi đã sửa.",
            "- Tốt nghiệp chuyên ngành CNTT hoặc liên quan.\n- Cẩn thận, tỉ mỉ, có tư duy logic.\n- Ưu tiên ứng viên có chứng chỉ ISTQB.",
            "- Được đào tạo kiểm thử tự động.\n- Đầy đủ chế độ BHXH, BHYT.\n- Du lịch công ty hằng năm."],
        ['Chuyên viên Kinh doanh B2B', 'Kinh doanh / Bán hàng', '1 - 2 năm', 12, 25,
            "- Tìm kiếm, phát triển khách hàng doanh nghiệp mới.\n- Tư vấn giải pháp, lập báo giá và đàm phán hợp đồng.\n- Chăm sóc khách hàng hiện có, đảm bảo doanh số được giao.",
            "- Có ít nhất 1 năm kinh nghiệm bán hàng B2B.\n- Kỹ năng giao tiếp, thuyết phục tốt.\n- Có phương tiện đi lại, chịu được áp lực doanh số.",
            "- Lương cứng + hoa hồng hấp dẫn, không giới hạn thu nhập.\n- Thưởng quý, thưởng năm theo doanh số.\n- Lộ trình thăng tiến lên Trưởng nhóm."],
        ['Nhân viên Bán hàng tại cửa hàng', 'Kinh doanh / Bán hàng', 'Chưa có kinh nghiệm', 7, 10,
            "- Tư vấn, giới thiệu sản phẩm cho khách hàng tại cửa hàng.\n- Sắp xếp, trưng bày hàng hóa gọn gàng.\n- Thanh toán và kiểm kê hàng hóa cuối ca.",
            "- Tốt nghiệp THPT trở lên.\n- Ngoại hình ưa nhìn, giao tiếp tốt.\n- Có thể làm việc theo ca, kể cả cuối tuần.",
            "- Thưởng doanh số hằng tháng.\n- Được đào tạo kỹ năng bán hàng.\n- Hỗ trợ cơm trưa, đồng phục."],
        ['Nhân viên Marketing Online', 'Marketing / PR', '1 - 2 năm', 10, 18,
            "- Lên kế hoạch và triển khai quảng cáo Facebook, Google, TikTok.\n- Viết nội dung cho fanpage, website.\n- Theo dõi, báo cáo hiệu quả chiến dịch hằng tuần.",
            "- Có kinh nghiệm chạy quảng cáo Facebook Ads, Google Ads.\n- Biết sử dụng Canva hoặc Photoshop cơ bản.\n- Sáng tạo, nắm bắt xu hướng mạng xã hội.",
            "- Ngân sách quảng cáo lớn để thử nghiệm.\n- Thưởng theo KPI.\n- Môi trường năng động, sáng tạo."],
        ['Chuyên viên Content Marketing', 'Marketing / PR', 'Dưới 1 năm', 9, 14,
            "- Viết bài chuẩn SEO cho website và blog.\n- Lên ý tưởng kịch bản video ngắn.\n- Phối hợp với thiết kế để sản xuất nội dung hình ảnh.",
            "- Khả năng viết tốt, đúng chính tả và ngữ pháp tiếng Việt.\n- Hiểu biết cơ bản về SEO.\n- Ưu tiên có portfolio bài viết.",
            "- Được đào tạo về SEO và marketing nội dung.\n- Thời gian làm việc linh hoạt.\n- Đầy đủ chế độ theo luật lao động."],
        ['Kế toán tổng hợp', 'Kế toán / Tài chính', '2 - 5 năm', 13, 20,
            "- Hạch toán các nghiệp vụ kế toán phát sinh.\n- Lập báo cáo tài chính, báo cáo thuế định kỳ.\n- Làm việc với cơ quan thuế, kiểm toán.",
            "- Tốt nghiệp Đại học chuyên ngành Kế toán, Tài chính.\n- Tối thiểu 2 năm kinh nghiệm kế toán tổng hợp.\n- Thành thạo Excel và phần mềm MISA.",
            "- Lương tháng 13, thưởng Tết.\n- Làm việc giờ hành chính, nghỉ thứ Bảy và Chủ nhật.\n- Khám sức khỏe định kỳ."],
        ['Chuyên viên Phân tích Tín dụng', 'Kế toán / Tài chính', '1 - 2 năm', 15, 22,
            "- Thẩm định hồ sơ vay vốn của khách hàng cá nhân và doanh nghiệp.\n- Đánh giá rủi ro, đề xuất phương án cấp tín dụng.\n- Theo dõi, giám sát khoản vay sau giải ngân.",
            "- Tốt nghiệp Đại học khối Kinh tế, Tài chính - Ngân hàng.\n- Kỹ năng phân tích số liệu tốt.\n- Tiếng Anh giao tiếp là lợi thế.",
            "- Thu nhập hấp dẫn theo năng lực.\n- Ưu đãi lãi suất vay cho nhân viên.\n- Đào tạo nghiệp vụ bài bản."],
        ['Chuyên viên Tuyển dụng', 'Nhân sự / Hành chính', '1 - 2 năm', 10, 15,
            "- Đăng tin, sàng lọc hồ sơ và phỏng vấn ứng viên.\n- Xây dựng nguồn ứng viên cho các vị trí khó tuyển.\n- Tổ chức chương trình hội nhập cho nhân viên mới.",
            "- Tốt nghiệp chuyên ngành Quản trị nhân lực hoặc liên quan.\n- Có kinh nghiệm tuyển dụng số lượng lớn là lợi thế.\n- Giao tiếp tốt, chủ động.",
            "- Thưởng theo số lượng tuyển thành công.\n- Môi trường chuyên nghiệp.\n- Đầy đủ BHXH, BHYT, BHTN."],
        ['Thực tập sinh Nhân sự', 'Nhân sự / Hành chính', 'Chưa có kinh nghiệm', 4, 6,
            "- Hỗ trợ đăng tin tuyển dụng, sắp xếp lịch phỏng vấn.\n- Cập nhật hồ sơ nhân sự.\n- Hỗ trợ tổ chức sự kiện nội bộ.",
            "- Sinh viên năm 3, năm 4 chuyên ngành Nhân sự, Quản trị kinh doanh.\n- Có thể làm tối thiểu 4 buổi/tuần.\n- Nhanh nhẹn, ham học hỏi.",
            "- Trợ cấp thực tập hằng tháng.\n- Có cơ hội trở thành nhân viên chính thức.\n- Được hướng dẫn tận tình."],
        ['Nhân viên Thiết kế Đồ họa', 'Thiết kế / Sáng tạo', '1 - 2 năm', 10, 17,
            "- Thiết kế ấn phẩm truyền thông: banner, poster, bao bì.\n- Thiết kế hình ảnh cho mạng xã hội và website.\n- Phối hợp với team Marketing thực hiện chiến dịch.",
            "- Thành thạo Photoshop, Illustrator; biết Figma là lợi thế.\n- Có portfolio các sản phẩm đã thực hiện.\n- Có gu thẩm mỹ tốt, sáng tạo.",
            "- Được trang bị máy tính cấu hình cao.\n- Thưởng dự án.\n- Môi trường sáng tạo, thoải mái."],
        ['Biên tập viên Dựng video', 'Thiết kế / Sáng tạo', 'Dưới 1 năm', 9, 15,
            "- Dựng video quảng cáo, video ngắn cho TikTok, YouTube.\n- Chỉnh màu, thêm hiệu ứng, phụ đề.\n- Lên ý tưởng hình ảnh cùng biên kịch.",
            "- Thành thạo Premiere Pro, After Effects hoặc CapCut.\n- Nắm bắt xu hướng video ngắn.\n- Có sản phẩm mẫu là lợi thế lớn.",
            "- Lương thỏa thuận theo năng lực.\n- Thưởng theo lượt xem video.\n- Được tham gia các dự án lớn."],
        ['Nhân viên Chăm sóc khách hàng', 'Chăm sóc khách hàng', 'Chưa có kinh nghiệm', 8, 12,
            "- Tiếp nhận và giải đáp thắc mắc của khách hàng qua điện thoại, chat.\n- Xử lý khiếu nại, phản hồi của khách hàng.\n- Ghi nhận thông tin vào hệ thống CRM.",
            "- Tốt nghiệp Trung cấp trở lên.\n- Giọng nói dễ nghe, không nói ngọng.\n- Kiên nhẫn, thân thiện.",
            "- Đào tạo nghiệp vụ có lương.\n- Phụ cấp ca tối, ăn trưa.\n- Thưởng thi đua hằng tháng."],
        ['Kỹ sư Tự động hóa', 'Sản xuất / Kỹ thuật', '2 - 5 năm', 18, 28,
            "- Vận hành, bảo trì hệ thống dây chuyền tự động.\n- Lập trình PLC, cải tiến quy trình sản xuất.\n- Xử lý sự cố kỹ thuật phát sinh.",
            "- Tốt nghiệp Đại học chuyên ngành Tự động hóa, Điện - Điện tử.\n- Thành thạo lập trình PLC Siemens hoặc Mitsubishi.\n- Đọc hiểu bản vẽ kỹ thuật, tiếng Anh chuyên ngành.\n- Có khả năng tăng ca khi cần.",
            "- Xe đưa đón, bữa ăn tại nhà máy.\n- Thưởng năng suất, thưởng sáng kiến.\n- Cơ hội đào tạo tại nước ngoài."],
        ['Nhân viên Kho vận', 'Sản xuất / Kỹ thuật', 'Dưới 1 năm', 8, 11,
            "- Nhập, xuất, kiểm đếm hàng hóa trong kho.\n- Sắp xếp hàng hóa theo đúng quy định.\n- Cập nhật số liệu tồn kho trên phần mềm.",
            "- Sức khỏe tốt, trung thực.\n- Biết sử dụng máy tính cơ bản.\n- Ưu tiên có chứng chỉ lái xe nâng.",
            "- Phụ cấp chuyên cần, ăn ca.\n- Tăng lương định kỳ.\n- Đầy đủ chế độ bảo hiểm."],
        ['Giáo viên Tiếng Anh', 'Giáo dục / Đào tạo', '1 - 2 năm', 12, 20,
            "- Giảng dạy tiếng Anh cho học sinh tiểu học và THCS.\n- Soạn giáo án, đánh giá tiến bộ của học viên.\n- Trao đổi với phụ huynh về kết quả học tập.",
            "- Tốt nghiệp Đại học Sư phạm Tiếng Anh hoặc Ngôn ngữ Anh.\n- Có chứng chỉ IELTS 7.0 hoặc tương đương.\n- Yêu trẻ, nhiệt tình.",
            "- Thu nhập theo giờ dạy hấp dẫn.\n- Được đào tạo phương pháp giảng dạy hiện đại.\n- Nghỉ hè có lương."],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$title, $category, $experience, $salaryMin, $salaryMax, $description, $requirements, $benefits] = fake()->randomElement(self::TEMPLATES);

        return [
            'user_id' => User::factory()->employer(),
            'title' => $title,
            'company_name' => fake()->randomElement(self::COMPANIES),
            'category' => $category,
            'location' => fake()->randomElement(['Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương']),
            'job_type' => str_starts_with($title, 'Thực tập') ? JobType::Internship : JobType::FullTime,
            'experience' => $experience,
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'description' => $description,
            'requirements' => $requirements,
            'benefits' => $benefits,
            'deadline' => now()->addDays(fake()->numberBetween(7, 45)),
            'status' => JobPostStatus::Open,
        ];
    }

    /**
     * Uses each template once, in order — for seeding a varied catalogue.
     */
    public function eachTemplate(): static
    {
        return $this->count(count(self::TEMPLATES))->sequence(...array_map(fn (array $template): array => [
            'title' => $template[0],
            'category' => $template[1],
            'experience' => $template[2],
            'salary_min' => $template[3],
            'salary_max' => $template[4],
            'description' => $template[5],
            'requirements' => $template[6],
            'benefits' => $template[7],
            'job_type' => str_starts_with($template[0], 'Thực tập') ? JobType::Internship : JobType::FullTime,
        ], self::TEMPLATES));
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => JobPostStatus::Closed]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['deadline' => now()->subDay()]);
    }
}
