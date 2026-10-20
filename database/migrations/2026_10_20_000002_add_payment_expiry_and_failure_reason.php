<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->index();
            }
            if (! Schema::hasColumn('payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable();
            }
        });

        DB::table('payments')->where('status', 'PENDING')->whereNull('expires_at')
            ->update(['expires_at' => now()->addMinutes(15)]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'failure_reason')) {
                $table->dropColumn('failure_reason');
            }
            if (Schema::hasColumn('payments', 'expires_at')) {
                $table->dropIndex(['expires_at']);
                $table->dropColumn('expires_at');
            }
        });
    }
};
