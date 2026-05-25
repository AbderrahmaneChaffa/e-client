<?php

namespace App\Http\Controllers;

use App\Services\Notifications\NotificationFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationFeedService $feed): View
    {
        $perPage = min((int) $request->input('per_page', 20), 100);
        $notifications = $feed->paginate($request->user(), $request->only(['status', 'alert_type', 'severity']), $perPage);
        $unreadCount = $feed->unreadCount($request->user());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function feed(Request $request, NotificationFeedService $feed): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 12), 1), 30);

        return response()->json($feed->feed($request->user(), $limit));
    }

    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->markAsRead();

        return redirect()->to($this->safeRedirectUrl((string) data_get($notification->data, 'url')));
    }

    public function markAsRead(Request $request, DatabaseNotification $notification, NotificationFeedService $feed): JsonResponse|RedirectResponse
    {
        $this->authorizeNotification($request, $notification);
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'notification' => $feed->format($notification->fresh()),
                'unread_count' => $feed->unreadCount($request->user()),
            ]);
        }

        return back();
    }

    public function markAllAsRead(Request $request, NotificationFeedService $feed): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'unread_count' => $feed->unreadCount($request->user()),
            ]);
        }

        return back();
    }

    private function authorizeNotification(Request $request, DatabaseNotification $notification): void
    {
        abort_unless(
            $notification->notifiable_type === $request->user()->getMorphClass()
            && (string) $notification->notifiable_id === (string) $request->user()->getKey(),
            403
        );
    }

    private function safeRedirectUrl(string $url): string
    {
        if ($url === '') {
            return route('dashboard');
        }

        if (Str::startsWith($url, ['/'])) {
            return $url;
        }

        if (Str::startsWith($url, [url('/')])) {
            return $url;
        }

        return route('dashboard');
    }
}
