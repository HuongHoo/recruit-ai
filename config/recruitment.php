<?php

return [

    /*
    | Provinces/cities a candidate can choose as preferred work locations.
    */
    'locations' => [
        'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ', 'Bình Dương', 'Đồng Nai',
        'Bắc Ninh', 'Hưng Yên', 'Quảng Ninh', 'Thanh Hóa', 'Nghệ An', 'Huế', 'Khánh Hòa',
        'Lâm Đồng', 'Bà Rịa - Vũng Tàu', 'Long An', 'Làm việc từ xa',
    ],

    'degrees' => ['Trung học phổ thông', 'Trung cấp', 'Cao đẳng', 'Đại học', 'Thạc sĩ', 'Tiến sĩ', 'Chứng chỉ'],

    'genders' => ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'],

    'job_categories' => [
        'IT - Phần mềm', 'Kinh doanh / Bán hàng', 'Marketing / PR', 'Kế toán / Tài chính', 'Nhân sự / Hành chính',
        'Thiết kế / Sáng tạo', 'Chăm sóc khách hàng', 'Sản xuất / Kỹ thuật', 'Giáo dục / Đào tạo', 'Khác',
    ],

    'experience_levels' => ['Chưa có kinh nghiệm', 'Dưới 1 năm', '1 - 2 năm', '2 - 5 năm', 'Trên 5 năm'],

    'jobs_per_page' => 10,

    /*
    | Options for the employer sign-up form.
    */
    'employer' => [
        'logo_max_kb' => 2048,
        'business_models' => [
            'in_house' => [
                'name' => 'Doanh nghiệp tuyển dụng nội bộ',
                'description' => 'Dành cho HR / phòng Tuyển dụng tìm kiếm và tuyển nhân sự cho chính công ty.',
            ],
            'agency' => [
                'name' => 'Công ty Headhunt / Tuyển dụng dịch vụ (Agency)',
                'description' => 'Dành cho đơn vị Agency, Headhunter chuyên cung cấp và giới thiệu ứng viên cho các công ty khách hàng.',
            ],
        ],
        'company_sizes' => [
            '1-9' => '1 - 9 nhân viên',
            '10-24' => '10 - 24 nhân viên',
            '25-99' => '25 - 99 nhân viên',
            '100-499' => '100 - 499 nhân viên',
            '500-999' => '500 - 999 nhân viên',
            '1000+' => 'Trên 1000 nhân viên',
        ],
    ],

    'max_job_alerts' => 5,

    /*
    | AI mock interview ("Phỏng vấn mô phỏng với AI"). 'speech' is the BCP-47 tag the browser uses
    | to read questions aloud and to transcribe the candidate's voice; 'prompt' names the language for the AI.
    */
    'mock_interview' => [
        'question_count' => 6,
        'max_answer_chars' => 3000,
        'languages' => [
            'vi' => ['name' => 'Tiếng Việt', 'speech' => 'vi-VN', 'prompt' => 'tiếng Việt', 'test_phrase' => 'Xin chào, tôi đã sẵn sàng phỏng vấn.'],
            'en' => ['name' => 'Tiếng Anh', 'speech' => 'en-US', 'prompt' => 'tiếng Anh', 'test_phrase' => 'Hello, I am ready for the interview.'],
            'ja' => ['name' => 'Tiếng Nhật', 'speech' => 'ja-JP', 'prompt' => 'tiếng Nhật', 'test_phrase' => 'こんにちは、面接の準備ができました。'],
            'zh' => ['name' => 'Tiếng Trung', 'speech' => 'zh-CN', 'prompt' => 'tiếng Trung giản thể', 'test_phrase' => '你好，我已经准备好面试了。'],
        ],

        /*
        | The animated AI interviewer the candidate talks to. The name is spoken in the interview
        | language. "tts_voice" is the Gemini voice that reads the lines (it speaks every interview
        | language); "voice" lists keywords of browser TTS voices of that gender, used only as a backup.
        */
        'interviewers' => [
            'female' => [
                'label' => 'Nữ',
                'tts_voice' => 'Kore',
                'names' => ['vi' => 'Ngọc Lan', 'en' => 'Emma', 'ja' => '佐藤 由紀', 'zh' => '李娜'],
                'voice' => ['female', 'woman', 'hoaimy', 'linh', 'an', 'zira', 'aria', 'jenny', 'samantha', 'victoria', 'karen', 'susan', 'hazel', 'emma', 'ava', 'nanami', 'haruka', 'ayumi', 'kyoko', 'xiaoxiao', 'xiaoyi', 'huihui', 'yaoyao', 'ting-ting', 'mei-jia'],
            ],
            'male' => [
                'label' => 'Nam',
                'tts_voice' => 'Charon',
                'names' => ['vi' => 'Minh Quân', 'en' => 'David', 'ja' => '田中 健一', 'zh' => '王伟'],
                'voice' => ['male', 'man', 'namminh', 'david', 'mark', 'guy', 'ryan', 'george', 'daniel', 'alex', 'fred', 'andrew', 'brian', 'christopher', 'eric', 'keita', 'ichiro', 'kangkang', 'yunxi', 'yunyang', 'yunjian'],
            ],
        ],

        /*
        | What the interviewer says around the questions, per interview language.
        | Placeholders: :candidate, :name, :title, :company.
        */
        'script' => [
            'vi' => [
                'title' => 'Trưởng phòng Nhân sự',
                'greeting' => 'Xin chào :candidate, tôi là :name, :title của :company. Cảm ơn bạn đã dành thời gian tham gia buổi phỏng vấn hôm nay. Chúng ta bắt đầu nhé.',
                'resume' => 'Chào mừng :candidate quay lại. Chúng ta tiếp tục buổi phỏng vấn nhé.',
                'acks' => ['Cảm ơn bạn.', 'Được rồi, cảm ơn bạn.', 'Tôi hiểu rồi.', 'Rất tốt, chúng ta sang câu tiếp theo nhé.'],
                'skip' => 'Không sao, chúng ta sang câu tiếp theo.',
                'closing' => 'Cảm ơn :candidate đã trả lời các câu hỏi. Buổi phỏng vấn đến đây là kết thúc, tôi sẽ gửi nhận xét cho bạn ngay sau đây.',
            ],
            'en' => [
                'title' => 'HR Manager',
                'greeting' => "Hello :candidate, I'm :name, :title at :company. Thank you for taking the time to meet with us today. Let's get started.",
                'resume' => "Welcome back, :candidate. Let's continue the interview.",
                'acks' => ['Thank you.', 'Alright, thank you.', 'I see.', "Great, let's move on."],
                'skip' => "That's okay, let's move on to the next question.",
                'closing' => "Thank you, :candidate. That's the end of our interview. I'll share my feedback with you shortly.",
            ],
            'ja' => [
                'title' => '人事部長',
                'greeting' => 'こんにちは、:candidateさん。:companyの:title、:nameです。本日はお時間をいただきありがとうございます。それでは始めましょう。',
                'resume' => 'おかえりなさい、:candidateさん。面接を続けましょう。',
                'acks' => ['ありがとうございます。', 'なるほど、わかりました。', 'はい、ありがとうございます。', 'では、次の質問です。'],
                'skip' => '大丈夫です。次の質問に進みましょう。',
                'closing' => ':candidateさん、ありがとうございました。面接は以上です。このあとフィードバックをお送りします。',
            ],
            'zh' => [
                'title' => '人事经理',
                'greeting' => '你好，:candidate。我是:company的:title:name。感谢你今天抽出时间参加面试。我们开始吧。',
                'resume' => '欢迎回来，:candidate。我们继续面试吧。',
                'acks' => ['谢谢。', '好的，谢谢你。', '我明白了。', '很好，我们进入下一个问题。'],
                'skip' => '没关系，我们进入下一个问题。',
                'closing' => '谢谢你，:candidate。今天的面试到此结束，我稍后会把反馈发给你。',
            ],
        ],
    ],

    /*
    | Video CV: the candidate answers a few interview questions on camera (one clip per question).
    | Everything stays on the private disk; employers see it when the candidate applies, anyone else
    | only through the share link the candidate creates (and can revoke).
    */
    'video_cv' => [
        'max_questions' => 5,
        'max_answer_seconds' => 120,
        'max_upload_kb' => 61440,
        'max_photo_kb' => 2048,
        'mimetypes' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-matroska'],
        'extensions' => 'mp4, webm, mov',
        'default_questions' => [
            [
                'question' => 'Hãy giới thiệu ngắn gọn về bản thân bạn.',
                'hint' => 'Họ tên, chuyên môn, số năm kinh nghiệm và vị trí bạn đang tìm. Kết thúc bằng một điểm mạnh nổi bật.',
            ],
            [
                'question' => 'Kể về một dự án hoặc thành tích bạn tự hào nhất.',
                'hint' => 'Bối cảnh → việc bạn làm → kết quả có số liệu (tăng bao nhiêu %, tiết kiệm bao nhiêu thời gian...).',
            ],
            [
                'question' => 'Vì sao bạn phù hợp với vị trí bạn đang ứng tuyển?',
                'hint' => 'Nối kỹ năng của bạn với yêu cầu công việc, thêm mong muốn phát triển trong 1–2 năm tới.',
            ],
        ],
    ],

    'cv' => [
        'max_upload_kb' => 5120,
        'max_per_user' => 10,
        'accent_colors' => ['#2563eb', '#059669', '#7c3aed', '#dc2626', '#0f172a', '#ea580c'],
        /*
        | Online CV templates. Each key has a Blade view in resources/views/candidate/cvs/templates.
        | 'suitable' and 'ats' are shown on the public template gallery.
        */
        'templates' => [
            'ats' => [
                'name' => 'CV chuẩn ATS',
                'description' => 'Một cột, chữ đen trắng rõ ràng — phần mềm lọc CV (ATS) đọc chính xác nhất.',
                'suitable' => 'Ứng tuyển công ty Việt Nam và công ty đa quốc gia dùng phần mềm lọc CV.',
                'ats' => true,
            ],
            'classic' => [
                'name' => 'Cổ điển',
                'description' => 'Một cột, trang trọng — phù hợp ngân hàng, kế toán, hành chính.',
                'suitable' => 'Ngân hàng, kế toán, hành chính, cơ quan nhà nước.',
                'ats' => true,
            ],
            'modern' => [
                'name' => 'Hiện đại',
                'description' => 'Hai cột với thanh bên màu — phù hợp IT, marketing, kinh doanh.',
                'suitable' => 'IT, marketing, kinh doanh, startup.',
                'ats' => false,
            ],
            'minimal' => [
                'name' => 'Tối giản',
                'description' => 'Thoáng, nhiều khoảng trắng — phù hợp thiết kế, sáng tạo.',
                'suitable' => 'Thiết kế, sáng tạo, truyền thông.',
                'ats' => false,
            ],
            'chinese' => [
                'name' => 'CV tiếng Trung',
                'description' => 'Tiêu đề song ngữ Trung - Việt, bố cục 个人简历 quen thuộc.',
                'suitable' => 'Cho nhà tuyển dụng nói tiếng Trung (Đài Loan, Trung Quốc, Hồng Kông).',
                'ats' => false,
                'language' => 'Tiếng Trung - Tiếng Việt',
            ],
            'japanese' => [
                'name' => 'CV tiếng Nhật',
                'description' => 'Dạng bảng 履歴書 (rirekisho) cho công ty Nhật.',
                'suitable' => 'Công ty Nhật Bản, chương trình kỹ sư, thực tập sinh.',
                'ats' => false,
                'language' => 'Tiếng Nhật - Tiếng Việt',
                'photo' => true,
            ],
            'worker' => [
                'name' => 'CV công nhân',
                'description' => 'Ngắn gọn, chữ to dễ đọc, hợp việc làm nhà máy.',
                'suitable' => 'Công nhân, lao động phổ thông, khu công nghiệp.',
                'ats' => true,
            ],
        ],
    ],

    /*
    | Job promotion packages ("Gói dịch vụ"). Prices in VND per job post, by number of days shown.
    | 'priority' orders promoted jobs (higher first); 'features' are keys of 'package_features'.
    */
    'packages' => [
        'basic' => [
            'name' => 'Basic',
            'priority' => 1,
            'prices' => [7 => 250000, 14 => 450000, 30 => 850000],
            'summary' => 'Tin nổi bật hơn với chi phí thấp.',
            'rank' => 'Ưu tiên sau Pro và Pro Max',
            'features' => ['highlight', 'list', 'related'],
        ],
        'pro' => [
            'name' => 'Pro',
            'priority' => 2,
            'prices' => [7 => 420000, 14 => 750000, 30 => 1400000],
            'summary' => 'Tuyển thường xuyên, tin có nhãn nổi bật để thu hút ứng viên.',
            'rank' => 'Ưu tiên chỉ sau Pro Max',
            'features' => ['highlight', 'list', 'related', 'badge'],
            'recommended' => true,
        ],
        'promax' => [
            'name' => 'Pro Max',
            'priority' => 3,
            'prices' => [7 => 1050000, 14 => 1900000, 30 => 3500000],
            'summary' => 'Đứng đầu kết quả và được ưu tiên ở mục việc làm nổi bật trên trang chủ.',
            'rank' => 'Luôn ở nhóm đầu kết quả tìm kiếm',
            'features' => ['highlight', 'list', 'related', 'badge', 'home'],
        ],
    ],

    'package_features' => [
        'highlight' => 'Thẻ việc làm có nền xanh nổi bật',
        'list' => 'Ưu tiên trong danh sách việc làm',
        'related' => 'Ưu tiên trong việc làm liên quan',
        'badge' => 'Nhãn gói hiển thị trên tin tuyển dụng',
        'home' => 'Ưu tiên ở mục việc làm nổi bật trang chủ',
    ],

    /*
    | Bank account shown on the VietQR payment page. Override in .env.
    */
    'payment' => [
        'bank_id' => env('PAYMENT_BANK_ID', 'VCB'),
        'account_no' => env('PAYMENT_ACCOUNT_NO', '0123456789'),
        'account_name' => env('PAYMENT_ACCOUNT_NAME', 'CONG TY TUYEN DUNG'),
        // True while no real account is configured: the payment page then warns not to transfer.
        'is_demo' => env('PAYMENT_ACCOUNT_NO') === null,
    ],
];
