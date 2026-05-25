<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class NotificationFeedService
{
    public function feed(User $user, int $limit = 12): array
    {
        if (! Schema::hasTable('notifications')) {
            return [
                'notifications' => [],
                'unread_count' => 0,
                'server_time' => now()->toIso8601String(),
            ];
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->format($notification))
            ->values()
            ->all();

        return [
            'notifications' => $notifications,
            'unread_count' => $this->unreadCount($user),
            'server_time' => now()->toIso8601String(),
        ];
    }

    public function paginate(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        if (! Schema::hasTable('notifications')) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = $user->notifications()->latest();

        if (($filters['status'] ?? null) === 'unread') {
            $query->whereNull('read_at');
        }

        if (($filters['status'] ?? null) === 'read') {
            $query->whereNotNull('read_at');
        }

        if (! empty($filters['alert_type'])) {
            $query->where('data->alert_type', $filters['alert_type']);
        }

        if (! empty($filters['severity'])) {
            $query->where('data->severity', $filters['severity']);
        }

        return $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification) => $this->format($notification));
    }

    public function unreadCount(User $user): int
    {
        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        return (int) $user->unreadNotifications()->count();
    }

    public function format(DatabaseNotification $notification): array
    {
        $data = $notification->data ?? [];
        $createdAt = $notification->created_at;

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'role' => $data['role'] ?? null,
            'category' => $data['category'] ?? 'notification',
            'alert_type' => $data['alert_type'] ?? 'info',
            'title' => $data['title'] ?? 'Notification',
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? 'open',
            'severity' => $data['severity'] ?? 'info',
            'icon' => $data['icon'] ?? 'bell',
            'color' => $data['color'] ?? ($data['severity'] ?? 'info'),
            'url' => $data['url'] ?? route('dashboard'),
            'open_url' => route('notifications.open', $notification),
            'action_label' => $data['action_label'] ?? 'Ouvrir',
            'meta' => $data['meta'] ?? [],
            'timestamp' => $data['timestamp'] ?? $createdAt?->toIso8601String(),
            'created_at' => $createdAt?->toIso8601String(),
            'created_at_label' => $createdAt?->format('d/m/Y H:i'),
            'time_ago' => $createdAt?->diffForHumans(),
            'read_at' => $notification->read_at?->toIso8601String(),
            'read_at_label' => $notification->read_at?->format('d/m/Y H:i'),
            'is_read' => $notification->read_at !== null,
        ];
    }
}
