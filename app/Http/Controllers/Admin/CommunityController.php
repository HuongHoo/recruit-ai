<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Community moderation: hide/unhide or delete posts, delete comments, resolve member reports.
 */
class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), ['reported', 'hidden'], true) ? $request->query('filter') : 'all';
        $search = trim((string) $request->query('q'));
        $openReports = fn ($q) => $q->whereNull('resolved_at');

        $posts = CommunityPost::query()
            ->with('author')
            ->withCount(['reports as open_reports_count' => $openReports])
            ->when($filter === 'reported', fn ($q) => $q->whereHas('reports', $openReports))
            ->when($filter === 'hidden', fn ($q) => $q->where('is_hidden', true))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.community.index', [
            'posts' => $posts,
            'filter' => $filter,
            'openReportCount' => CommunityReport::query()->whereNull('resolved_at')->count(),
        ]);
    }

    public function reports(Request $request): View
    {
        $resolved = $request->boolean('resolved');

        return view('admin.community.reports', [
            'reports' => CommunityReport::query()
                ->with(['reporter', 'reportable' => fn ($morph) => $morph->morphWith([CommunityComment::class => ['post']])])
                ->when($resolved, fn ($q) => $q->whereNotNull('resolved_at'), fn ($q) => $q->whereNull('resolved_at'))
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'resolved' => $resolved,
        ]);
    }

    public function toggle(CommunityPost $post): RedirectResponse
    {
        $post->is_hidden = ! $post->is_hidden;
        $post->save();

        return back()->with('status', $post->is_hidden ? 'Đã ẩn bài viết.' : 'Đã hiện lại bài viết.');
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.community.index')->with('status', 'Đã xoá bài viết.');
    }

    public function destroyComment(CommunityComment $comment): RedirectResponse
    {
        $comment->deleteAndRecount();

        return back()->with('status', 'Đã xoá bình luận.');
    }

    public function resolve(CommunityReport $report): RedirectResponse
    {
        $report->forceFill(['resolved_at' => now()])->save();

        return back()->with('status', 'Đã đánh dấu báo cáo là đã xử lý.');
    }
}
