<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role')
                    ->default('client')
                    ->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'is_validated')) {
            Schema::table('users', function (Blueprint $table): void {
                $after = Schema::hasColumn('users', 'role') ? 'role' : 'remember_token';

                $table->boolean('is_validated')
                    ->nullable()
                    ->default(false)
                    ->after($after);
            });
        }

        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('deleted_at')
                    ->nullable()
                    ->after('updated_at');
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::table('users')
                ->whereNotNull('role')
                ->update(['role' => DB::raw('LOWER(role)')]);

            if (DB::connection()->getDriverName() === 'mysql') {
                try {
                    DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'client'");
                } catch (Throwable $e) {
                    // Schéma déjà conforme ou privilèges insuffisants sur un environnement restreint.
                }
            }
        }

        if (Schema::hasColumn('users', 'client_id') && ! Schema::hasIndex('users', 'users_client_id_unique')) {
            $hasDuplicates = DB::table('users')
                ->whereNotNull('client_id')
                ->select('client_id')
                ->groupBy('client_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($hasDuplicates) {
                throw new RuntimeException("Impossible d'ajouter l'index unique sur users.client_id : des doublons existent déjà.");
            }

            Schema::table('users', function (Blueprint $table): void {
                $table->unique('client_id', 'users_client_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'users_client_id_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_client_id_unique');
            });
        }

        if (Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasColumn('users', 'is_validated')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_validated');
            });
        }
    }
};
