<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            if (! Schema::hasColumn('factures', 'verification_status')) {
                $table->string('verification_status', 20)->default('ok')->index();
            }

            if (! Schema::hasColumn('factures', 'verification_flags')) {
                $table->json('verification_flags')->nullable();
            }

            if (! Schema::hasColumn('factures', 'last_verified_at')) {
                $table->timestamp('last_verified_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            if (Schema::hasColumn('factures', 'last_verified_at')) {
                $table->dropColumn('last_verified_at');
            }

            if (Schema::hasColumn('factures', 'verification_flags')) {
                $table->dropColumn('verification_flags');
            }

            if (Schema::hasColumn('factures', 'verification_status')) {
                $table->dropColumn('verification_status');
            }
        });
    }
};
