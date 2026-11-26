<?php

namespace App\Support;

use App\Models\CandidateProfile;
use App\Models\Cv;
use App\Models\Education;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Sample CV content for template previews (gallery, home page). Never a real user's data.
 */
class CvSampleData
{
    /**
     * Unsaved models shaped exactly like the real CV preview expects.
     *
     * @return array{cv: Cv, user: User, profile: CandidateProfile, experiences: Collection<int, Experience>, educations: Collection<int, Education>}
     */
    public static function make(string $template = 'ats'): array
    {
        $cv = new Cv([
            'title' => 'CV mẫu',
            'template' => $template,
            'accent_color' => '#2563eb',
            'objective' => 'Chuyên viên marketing số với 4 năm kinh nghiệm quản lý chiến dịch đa kênh cho ngành bán lẻ. Tăng doanh thu từ kênh online 35% trong 12 tháng. Mục tiêu: dẫn dắt nhóm performance marketing tại công ty thương mại điện tử.',
        ]);

        $user = new User(['name' => 'Nguyễn Minh Anh', 'email' => 'minhanh.nguyen@example.com']);

        $profile = new CandidateProfile([
            'phone' => '0912 345 678',
            'address' => 'TP. Hồ Chí Minh',
            'headline' => 'Chuyên viên Performance Marketing',
            'summary' => 'Chuyên viên marketing số với 4 năm kinh nghiệm.',
            'desired_salary_min' => 25,
            'desired_salary_max' => 35,
            'desired_locations' => ['Hồ Chí Minh', 'Làm việc từ xa'],
            'skills' => ['Google Ads', 'Meta Ads', 'Google Analytics 4', 'SEO', 'Excel nâng cao', 'Tiếng Anh (IELTS 7.0)', 'Quản lý ngân sách'],
        ]);

        $experiences = collect([
            new Experience([
                'company' => 'Công ty CP Bán lẻ ABC',
                'position' => 'Chuyên viên Performance Marketing',
                'start_date' => '2023-03-01',
                'is_current' => true,
                'description' => "Quản lý ngân sách quảng cáo 2 tỷ đồng/tháng trên Google Ads và Meta Ads\nTăng tỷ lệ chuyển đổi 18% nhờ tối ưu landing page và phân khúc khách hàng\nPhối hợp với 3 bộ phận để ra mắt 12 chiến dịch theo mùa",
            ]),
            new Experience([
                'company' => 'Agency Quảng cáo XYZ',
                'position' => 'Nhân viên Digital Marketing',
                'start_date' => '2021-06-01',
                'end_date' => '2023-02-01',
                'is_current' => false,
                'description' => "Vận hành chiến dịch cho 8 khách hàng ngành F&B\nGiảm chi phí mỗi khách hàng tiềm năng 22% sau 6 tháng",
            ]),
        ]);

        $educations = collect([
            new Education([
                'school' => 'Đại học Kinh tế TP. Hồ Chí Minh',
                'major' => 'Marketing',
                'degree' => 'Cử nhân',
                'start_date' => '2017-09-01',
                'end_date' => '2021-06-01',
                'description' => 'Tốt nghiệp loại Giỏi, GPA 3.5/4.',
            ]),
        ]);

        return compact('cv', 'user', 'profile', 'experiences', 'educations');
    }
}
