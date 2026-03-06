<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly array $payload,
    ) {
    }

    public function via(object $notifiable): array
    {
        // 메일이 아니라 DB notifications 테이블에 저장
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        // notifications.type 컬럼에 class명이 아니라 짧은 타입 저장
        return $this->type;
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload + ['type' => $this->type];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
