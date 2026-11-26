@extends('layouts.public')
@section('title', 'Chính sách bảo mật')

@section('content')
    @include('legal._page', [
        'heading' => 'Chính sách bảo mật',
        'updatedAt' => '17/11/2026',
        'sections' => [
            'Giới thiệu' => config('app.name').' là hệ thống tuyển dụng trực tuyến kết nối ứng viên và nhà tuyển dụng. Chính sách này mô tả dữ liệu cá nhân chúng tôi thu thập, mục đích sử dụng và quyền của bạn đối với dữ liệu đó.',
            'Dữ liệu chúng tôi thu thập' => [
                'Khi bạn sử dụng dịch vụ, chúng tôi có thể thu thập:',
                [
                    'Thông tin tài khoản: họ tên, địa chỉ email, mật khẩu (được mã hóa một chiều).',
                    'Thông tin từ Google khi bạn chọn "Đăng nhập với Google": họ tên, địa chỉ email, ảnh đại diện và mã định danh tài khoản Google. Chúng tôi không nhận được mật khẩu Google của bạn.',
                    'Hồ sơ ứng tuyển: CV, kinh nghiệm, học vấn, kỹ năng và thông tin liên hệ do bạn cung cấp.',
                    'Thông tin doanh nghiệp đối với nhà tuyển dụng: tên công ty, mã số thuế, địa chỉ, tin tuyển dụng.',
                    'Dữ liệu kỹ thuật: địa chỉ IP, loại trình duyệt và thời điểm đăng nhập, dùng để bảo mật tài khoản.',
                ],
            ],
            'Mục đích sử dụng' => [
                [
                    'Tạo và quản lý tài khoản, xác thực khi bạn đăng nhập.',
                    'Gửi hồ sơ ứng tuyển tới nhà tuyển dụng mà bạn chọn ứng tuyển.',
                    'Gửi email thông báo: cảnh báo đăng nhập, trạng thái ứng tuyển, lịch phỏng vấn, việc làm phù hợp.',
                    'Phân tích mức độ phù hợp giữa CV và tin tuyển dụng để gợi ý cho bạn.',
                    'Phát hiện và ngăn chặn truy cập trái phép.',
                ],
            ],
            'Dữ liệu từ Google' => 'Việc sử dụng thông tin nhận được từ Google API tuân thủ Chính sách dữ liệu người dùng của Google API (Google API Services User Data Policy), bao gồm các yêu cầu về Sử dụng giới hạn (Limited Use). Chúng tôi chỉ dùng họ tên, email và ảnh đại diện để tạo tài khoản và đăng nhập; không bán, không chia sẻ cho bên thứ ba cho mục đích quảng cáo.',
            'Chia sẻ dữ liệu' => 'Chúng tôi không bán dữ liệu cá nhân. Hồ sơ của ứng viên chỉ được hiển thị cho nhà tuyển dụng khi ứng viên chủ động ứng tuyển. Dữ liệu chỉ được cung cấp cho cơ quan nhà nước khi có yêu cầu hợp pháp.',
            'Lưu trữ và bảo mật' => 'Dữ liệu được lưu trữ trên máy chủ của hệ thống, mật khẩu được mã hóa, kết nối được bảo vệ bằng HTTPS. Dữ liệu được giữ trong thời gian tài khoản còn hoạt động và được xóa khi bạn yêu cầu xóa tài khoản.',
            'Quyền của bạn' => [
                [
                    'Xem và chỉnh sửa thông tin cá nhân trong trang hồ sơ.',
                    'Yêu cầu xóa tài khoản và toàn bộ dữ liệu liên quan.',
                    'Thu hồi quyền truy cập của '.config('app.name').' vào tài khoản Google bất cứ lúc nào tại myaccount.google.com/permissions.',
                ],
            ],
        ],
    ])
@endsection
