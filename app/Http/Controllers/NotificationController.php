<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in user's in-app notifications (header bell).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(15),
        ]);
    }

    /**
     * Marks one notification read and follows its link.
     */
    public function show(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // A community post can be deleted or hidden after the notification was sent.
        if (isset($notification->data['community_post_id'])) {
            $post = CommunityPost::query()->find($notification->data['community_post_id']);

            if (! $post?->isVisibleTo($request->user())) {
                return redirect()->route('community.index')->with('error', 'Bài viết này đã bị xoá hoặc tạm ẩn.');
            }
        }

        // Only follow links back into this site.
        return is_string($url) && str_starts_with($url, url('/'))
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
