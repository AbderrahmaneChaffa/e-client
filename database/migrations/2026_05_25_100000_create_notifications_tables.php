<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->json('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'idx_notifications_user_read_created');
            });
        }

        if (! Schema::hasTable('notification_deduplications')) {
            Schema::create('notification_deduplications', function (Blueprint $table) {
                $table->id();
                $table->morphs('notifiable');
                $table->uuid('notification_id')->nullable()->index();
                $table->string('dedupe_key');
                $table->char('dedupe_hash', 40);
                $table->timestamps();

                $table->unique(['notifiable_type', 'notifiable_id', 'dedupe_hash'], 'uq_notification_dedupe_user_hash');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deduplications');
        Schema::dropIfExists('notifications');
    }
};
