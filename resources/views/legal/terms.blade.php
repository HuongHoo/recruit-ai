@extends('layouts.public')
@section('title', 'Điều khoản sử dụng')

@section('content')
    @include('legal._page', [
        'heading' => 'Điều khoản sử dụng',
        'updatedAt' => '17/11/2026',
        'sections' => [
            'Chấp nhận điều khoản' => 'Khi đăng ký, đăng nhập (kể cả bằng tài khoản Google) hoặc sử dụng '.config('app.name').', bạn đồng ý với các điều khoản dưới đây và Chính sách bảo mật của chúng tôi.',
            'Tài khoản' => [
                [
                    'Bạn chịu trách nhiệm về tính chính xác của thông tin đã cung cấp.',
                    'Bạn có trách nhiệm giữ bí mật mật khẩu và thông báo ngay cho chúng tôi nếu phát hiện đăng nhập trái phép.',
                    'Mỗi lần đăng nhập bằng Google, hệ thống sẽ gửi email cảnh báo tới địa chỉ Gmail đã dùng để đăng nhập.',
                    'Chúng tôi có quyền khóa tài khoản vi phạm điều khoản.',
                ],
            ],
            'Đối với ứng viên' => 'Ứng viên cam kết hồ sơ, CV là trung thực và thuộc về chính mình. Ứng viên đồng ý để hồ sơ được gửi tới nhà tuyển dụng khi ứng tuyển.',
            'Đối với nhà tuyển dụng' => 'Nhà tuyển dụng phải cung cấp thông tin doanh nghiệp chính xác và chờ quản trị viên xác thực trước khi đăng tin. Tin tuyển dụng phải là vị trí có thật, không thu phí ứng viên, không phân biệt đối xử và tuân thủ pháp luật lao động Việt Nam. Dữ liệu ứng viên chỉ được dùng cho mục đích tuyển dụng.',
            'Hành vi bị cấm' => [
                [
                    'Đăng tải nội dung sai sự thật, lừa đảo, vi phạm pháp luật hoặc thuần phong mỹ tục.',
                    'Thu thập dữ liệu người dùng khác trái phép, sử dụng công cụ tự động gây quá tải hệ thống.',
                    'Mạo danh cá nhân hoặc tổ chức khác.',
                ],
            ],
            'Giới hạn trách nhiệm' => config('app.name').' là nền tảng trung gian, không bảo đảm kết quả tuyển dụng và không chịu trách nhiệm về thỏa thuận giữa ứng viên và nhà tuyển dụng.',
            'Thay đổi điều khoản' => 'Điều khoản có thể được cập nhật; phiên bản mới có hiệu lực kể từ khi đăng trên trang này.',
        ],
    ])
@endsection
