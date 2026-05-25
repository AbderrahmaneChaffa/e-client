<?php

namespace App\Services\Notifications;

use App\Models\User;
use Closure;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DeduplicatedNotificationDispatcher
{
    /**
     * @param  Closure(): Notification  $notificationFactory
     */
    public function send(User $user, Closure $notificationFactory, string $dedupeKey): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        if (! $this->claim($user, $dedupeKey)) {
            return false;
        }

        try {
            $notification = $notificationFactory();
            $user->notify($notification);

            if (Schema::hasTable('notification_deduplications') && isset($notification->id)) {
                DB::table('notification_deduplications')
                    ->where('notifiable_type', $user->getMorphClass())
                    ->where('notifiable_id', $user->getKey())
                    ->where('dedupe_hash', sha1($dedupeKey))
                    ->update(['notification_id' => $notification->id, 'updated_at' => now()]);
            }

            return true;
        } catch (\Throwable $e) {
            $this->release($user, $dedupeKey);

            throw $e;
        }
    }

    private function claim(User $user, string $dedupeKey): bool
    {
        if (! Schema::hasTable('notification_deduplications')) {
            return ! $this->notificationExists($user, $dedupeKey);
        }

        $now = now();

        return DB::table('notification_deduplications')->insertOrIgnore([
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->getKey(),
            'dedupe_key' => Str::limit($dedupeKey, 255, ''),
            'dedupe_hash' => sha1($dedupeKey),
            'created_at' => $now,
            'updated_at' => $now,
        ]) > 0;
    }

    private function release(User $user, string $dedupeKey): void
    {
        if (! Schema::hasTable('notification_deduplications')) {
            return;
        }

        DB::table('notification_deduplications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('dedupe_hash', sha1($dedupeKey))
            ->delete();
    }

    private function notificationExists(User $user, string $dedupeKey): bool
    {
        return $user->notifications()
            ->where('created_at', '>=', now()->subDays(180))
            ->where('data->dedupe_key', $dedupeKey)
            ->exists();
    }
}
