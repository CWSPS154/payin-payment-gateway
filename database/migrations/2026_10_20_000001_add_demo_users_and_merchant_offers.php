<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            if (! Schema::hasColumn('merchants', 'offer_name')) {
                $table->string('offer_name')->nullable();
            }
            if (! Schema::hasColumn('merchants', 'offer_amount')) {
                $table->decimal('offer_amount', 15, 2)->nullable();
            }
        });

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role', 20);
                $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'offer_amount')) {
                $table->dropColumn('offer_amount');
            }
            if (Schema::hasColumn('merchants', 'offer_name')) {
                $table->dropColumn('offer_name');
            }
        });
    }
};
