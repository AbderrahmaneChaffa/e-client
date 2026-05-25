<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AdminAlertNotification extends Notification
{
    public function __construct(private readonly array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    private function payload(): array
    {
        $timestamp = Arr::get($this->payload, 'timestamp', now()->toIso8601String());

        return [
            'role' => 'admin',
            'category' => Arr::get($this->payload, 'category', 'admin_alert'),
            'alert_type' => Arr::get($this->payload, 'alert_type', 'integrity_alert'),
            'title' => Str::limit((string) Arr::get($this->payload, 'title', 'Alerte admin'), 140),
            'description' => Str::limit((string) Arr::get($this->payload, 'description', ''), 500),
            'status' => Arr::get($this->payload, 'status', 'open'),
            'severity' => Arr::get($this->payload, 'severity', 'info'),
            'icon' => Arr::get($this->payload, 'icon', 'circle-alert'),
            'color' => Arr::get($this->payload, 'color', Arr::get($this->payload, 'severity', 'info')),
            'url' => Arr::get($this->payload, 'url', route('admin.dashboard')),
            'action_label' => Arr::get($this->payload, 'action_label', 'Voir details'),
            'dedupe_key' => Arr::get($this->payload, 'dedupe_key'),
            'timestamp' => $timestamp,
            'meta' => Arr::get($this->payload, 'meta', []),
        ];
    }
}
