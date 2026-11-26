<?php

use App\Http\Controllers\Admin\CommunityController as AdminCommunityController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Candidate\ApplicationController;
use App\Http\Controllers\Candidate\CvController;
use App\Http\Controllers\Candidate\CvMatchController;
use App\Http\Controllers\Candidate\DashboardController as CandidateDashboard;
use App\Http\Controllers\Candidate\EducationController;
use App\Http\Controllers\Candidate\ExperienceController;
use App\Http\Controllers\Candidate\JobAlertController;
use App\Http\Controllers\Candidate\MockInterviewController;
use App\Http\Controllers\Candidate\ProfileController;
use App\Http\Controllers\Candidate\SavedJobController;
use App\Http\Controllers\Candidate\VideoCvController;
use App\Http\Controllers\ChatAssistantController;
use App\Http\Controllers\CommunityCommentController;
use App\Http\Controllers\CommunityLikeController;
use App\Http\Controllers\CommunityPostController;
use App\Http\Controllers\CommunityReportController;
use App\Http\Controllers\CvBuilderLandingController;
use App\Http\Controllers\CvTemplateController;
use App\Http\Controllers\Employer\ApplicationController as EmployerApplicationController;
use App\Http\Controllers\Employer\CompanyController;
use App\Http\Controllers\Employer\DashboardController as EmployerDashboard;
use App\Http\Controllers\Employer\InterviewController;
use App\Http\Controllers\Employer\JobPostController;
use App\Http\Controllers\Employer\KpiController;
use App\Http\Controllers\Employer\PackageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\VideoCvLandingController;
use App\Http\Controllers\VideoCvPublicController;
use Illuminate\Support\Facades\Route;

/*
| Khách (chưa đăng nhập) — public pages, accessible to everyone.
*/
Route::get('/', HomeController::class)->name('home');

// Public legal pages; Google's OAuth consent screen (Branding page) links to these.
Route::view('/chinh-sach-bao-mat', 'legal.privacy')->name('legal.privacy');
Route::view('/dieu-khoan-su-dung', 'legal.terms')->name('legal.terms');
Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('/jobs/{job}', [JobController::class, 'show'])->name('jobs.show');
Route::get('/tao-cv', CvBuilderLandingController::class)->name('cv-builder');
Route::get('/video-cv', VideoCvLandingController::class)->name('video-cv');

// Video CV share links: viewable by anyone holding the link until the candidate revokes it.
Route::prefix('/v/{token}')->where(['token' => '[A-Za-z0-9]{32}'])->name('video-cv.public')->group(function () {
    Route::get('/', [VideoCvPublicController::class, 'show'])->name('');
    Route::get('/answers/{answer}', [VideoCvPublicController::class, 'answer'])->name('.answer');
    Route::get('/photo', [VideoCvPublicController::class, 'photo'])->name('.photo');
    Route::get('/cv', [VideoCvPublicController::class, 'cv'])->name('.cv');
});
Route::get('/mau-cv', [CvTemplateController::class, 'index'])->name('cv-templates.index');
Route::get('/mau-cv/{template}', [CvTemplateController::class, 'show'])->name('cv-templates.show');
Route::get('/bai-viet', [PostController::class, 'index'])->name('posts.index');
Route::get('/bai-viet/{post}', [PostController::class, 'show'])->name('posts.show');

// Chat assistant widget (guests included).
Route::post('/tro-ly/chat', ChatAssistantController::class)->middleware('throttle:10,1')->name('assistant.chat');

/*
| Cộng đồng — everyone reads, active members write.
*/
Route::prefix('/cong-dong')->name('community.')->group(function () {
    Route::get('/', [CommunityPostController::class, 'index'])->name('index');
    Route::get('/{post}', [CommunityPostController::class, 'show'])->whereNumber('post')->name('show');

    Route::middleware('auth')->group(function () {
        Route::get('/tao', [CommunityPostController::class, 'create'])->name('create');
        Route::post('/', [CommunityPostController::class, 'store'])->middleware('throttle:5,1')->name('store');
        Route::get('/{post}/sua', [CommunityPostController::class, 'edit'])->whereNumber('post')->name('edit');
        Route::put('/{post}', [CommunityPostController::class, 'update'])->whereNumber('post')->name('update');
        Route::delete('/{post}', [CommunityPostController::class, 'destroy'])->whereNumber('post')->name('destroy');

        Route::post('/{post}/binh-luan', [CommunityCommentController::class, 'store'])->whereNumber('post')->middleware('throttle:20,1')->name('comments.store');
        Route::put('/binh-luan/{comment}', [CommunityCommentController::class, 'update'])->whereNumber('comment')->middleware('throttle:20,1')->name('comments.update');
        Route::delete('/binh-luan/{comment}', [CommunityCommentController::class, 'destroy'])->whereNumber('comment')->name('comments.destroy');

        Route::post('/{post}/thich', CommunityLikeController::class)->whereNumber('post')->middleware('throttle:60,1')->name('like');
        Route::post('/bao-cao', CommunityReportController::class)->middleware('throttle:10,1')->name('report');
    });
});

/*
| Guest-only: logged-in users are redirected to their own dashboard.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/register/employer', [AuthController::class, 'registerEmployer'])->middleware('throttle:10,1')->name('register.employer');

    Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])->name('auth.social');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/thong-bao', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/thong-bao/doc-tat-ca', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/thong-bao/{id}', [NotificationController::class, 'show'])->name('notifications.show');
});

/*
| Ứng viên
*/
Route::middleware(['auth', 'role:candidate'])->prefix('candidate')->name('candidate.')->group(function () {
    Route::get('/dashboard', CandidateDashboard::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('educations', EducationController::class)->except(['index', 'show']);
    Route::resource('experiences', ExperienceController::class)->except(['index', 'show']);

    Route::post('/cvs/upload', [CvController::class, 'upload'])->name('cvs.upload');
    Route::post('/cvs/preview', [CvController::class, 'preview'])->name('cvs.preview')->middleware('throttle:120,1');
    Route::get('/cvs/{cv}/download', [CvController::class, 'download'])->name('cvs.download');
    Route::patch('/cvs/{cv}/default', [CvController::class, 'makeDefault'])->name('cvs.default');
    Route::patch('/cvs/{cv}/template', [CvController::class, 'applyTemplate'])->name('cvs.template');
    Route::resource('cvs', CvController::class);

    Route::get('/saved-jobs', [SavedJobController::class, 'index'])->name('saved-jobs.index');
    Route::post('/jobs/{job}/save', [SavedJobController::class, 'store'])->name('saved-jobs.store');
    Route::delete('/jobs/{job}/save', [SavedJobController::class, 'destroy'])->name('saved-jobs.destroy');

    Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
    Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])->name('applications.store')->middleware('throttle:20,1');

    Route::get('/jobs/{job}/match', [CvMatchController::class, 'show'])->name('cv-match.show');
    Route::post('/jobs/{job}/match', [CvMatchController::class, 'store'])->name('cv-match.store')->middleware('throttle:5,1');

    Route::get('/mock-interviews', [MockInterviewController::class, 'index'])->name('mock-interviews.index');
    Route::post('/jobs/{job}/mock-interview', [MockInterviewController::class, 'store'])->name('mock-interviews.store')->middleware('throttle:5,1');
    Route::get('/mock-interviews/{mockInterview}', [MockInterviewController::class, 'show'])->name('mock-interviews.show');
    Route::get('/mock-interviews/{mockInterview}/setup', [MockInterviewController::class, 'setup'])->name('mock-interviews.setup');
    Route::get('/mock-interviews/{mockInterview}/session', [MockInterviewController::class, 'session'])->name('mock-interviews.session');
    Route::get('/mock-interviews/{mockInterview}/speech/{line}', [MockInterviewController::class, 'speech'])->name('mock-interviews.speech')
        ->where('line', '[a-z]+(-\d{1,2})?')->middleware('throttle:60,1');
    Route::put('/mock-interviews/{mockInterview}/answer', [MockInterviewController::class, 'answer'])->name('mock-interviews.answer')->middleware('throttle:120,1');
    Route::post('/mock-interviews/{mockInterview}/finish', [MockInterviewController::class, 'finish'])->name('mock-interviews.finish')->middleware('throttle:5,1');

    Route::resource('job-alerts', JobAlertController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/video-cv', [VideoCvController::class, 'edit'])->name('video-cv.edit');
    Route::delete('/video-cv', [VideoCvController::class, 'destroy'])->name('video-cv.destroy');
    Route::put('/video-cv/questions', [VideoCvController::class, 'updateQuestions'])->name('video-cv.questions');
    Route::post('/video-cv/answers/{answer}', [VideoCvController::class, 'storeAnswer'])->name('video-cv.answers.store')->middleware('throttle:30,1');
    Route::delete('/video-cv/answers/{answer}', [VideoCvController::class, 'destroyAnswer'])->name('video-cv.answers.destroy');
    Route::get('/video-cv/answers/{answer}/file', [VideoCvController::class, 'answerFile'])->name('video-cv.answers.file');
    Route::put('/video-cv/info', [VideoCvController::class, 'updateInfo'])->name('video-cv.info');
    Route::get('/video-cv/photo', [VideoCvController::class, 'photo'])->name('video-cv.photo');
    Route::get('/video-cv/preview', [VideoCvController::class, 'preview'])->name('video-cv.preview');
    Route::post('/video-cv/share', [VideoCvController::class, 'share'])->name('video-cv.share');
    Route::delete('/video-cv/share', [VideoCvController::class, 'stopSharing'])->name('video-cv.share.destroy');
});

/*
| Nhà tuyển dụng
*/
Route::middleware(['auth', 'role:employer'])->prefix('employer')->name('employer.')->group(function () {
    Route::get('/dashboard', EmployerDashboard::class)->name('dashboard');
    Route::get('/kpi', KpiController::class)->name('kpi');

    Route::view('/settings', 'employer.settings')->name('settings');
    Route::get('/company', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company', [CompanyController::class, 'update'])->name('company.update');

    // Only verified companies may publish or change job posts.
    Route::resource('jobs', JobPostController::class)->only(['create', 'store', 'edit', 'update'])->middleware('company.verified');
    Route::resource('jobs', JobPostController::class)->only(['index', 'show', 'destroy']);

    Route::get('/applications', [EmployerApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}', [EmployerApplicationController::class, 'show'])->name('applications.show');
    Route::patch('/applications/{application}', [EmployerApplicationController::class, 'update'])->name('applications.update');
    Route::get('/applications/{application}/cv', [EmployerApplicationController::class, 'cv'])->name('applications.cv');
    Route::get('/applications/{application}/video/photo', [EmployerApplicationController::class, 'videoPhoto'])->name('applications.video-photo');
    Route::get('/applications/{application}/video/{answer}', [EmployerApplicationController::class, 'video'])->name('applications.video');

    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('/packages/cart', [PackageController::class, 'cart'])->name('packages.cart');
    Route::post('/packages/cart', [PackageController::class, 'addToCart'])->name('packages.cart.store');
    Route::delete('/packages/cart/{key}', [PackageController::class, 'removeFromCart'])->name('packages.cart.destroy');
    Route::post('/packages/buy', [PackageController::class, 'buy'])->name('packages.buy')->middleware('throttle:20,1');
    Route::post('/packages/checkout', [PackageController::class, 'checkout'])->name('packages.checkout')->middleware('throttle:20,1');
    Route::get('/orders/{order}', [PackageController::class, 'order'])->name('orders.show');
    Route::patch('/orders/{order}/cancel', [PackageController::class, 'cancel'])->name('orders.cancel');
    Route::get('/purchased', [PackageController::class, 'purchased'])->name('purchased.index');
    Route::post('/purchased/{package}/activate', [PackageController::class, 'activate'])->name('purchased.activate');

    Route::get('/interviews', [InterviewController::class, 'index'])->name('interviews.index');
    Route::post('/interviews', [InterviewController::class, 'store'])->name('interviews.store');
    Route::put('/interviews/{interview}', [InterviewController::class, 'update'])->name('interviews.update');
    Route::patch('/interviews/{interview}/status', [InterviewController::class, 'status'])->name('interviews.status');
    Route::delete('/interviews/{interview}', [InterviewController::class, 'destroy'])->name('interviews.destroy');
});

/*
| Quản trị viên
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('dashboard');

    Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company}', [AdminCompanyController::class, 'show'])->name('companies.show');
    Route::patch('/companies/{company}/approve', [AdminCompanyController::class, 'approve'])->name('companies.approve');
    Route::patch('/companies/{company}/reject', [AdminCompanyController::class, 'reject'])->name('companies.reject');

    Route::resource('posts', AdminPostController::class)->except('show');

    Route::prefix('/cong-dong')->name('community.')->group(function () {
        Route::get('/', [AdminCommunityController::class, 'index'])->name('index');
        Route::get('/bao-cao', [AdminCommunityController::class, 'reports'])->name('reports');
        Route::patch('/bao-cao/{report}', [AdminCommunityController::class, 'resolve'])->name('reports.resolve');
        Route::patch('/{post}/an-hien', [AdminCommunityController::class, 'toggle'])->name('toggle');
        Route::delete('/binh-luan/{comment}', [AdminCommunityController::class, 'destroyComment'])->name('comments.destroy');
        Route::delete('/{post}', [AdminCommunityController::class, 'destroy'])->whereNumber('post')->name('destroy');
    });

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/confirm', [AdminOrderController::class, 'confirm'])->name('orders.confirm');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
});
