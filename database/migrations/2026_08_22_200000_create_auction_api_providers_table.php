<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_api_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->text('api_key');
            $table->unsignedInteger('monthly_quota')->default(100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_active')->default(false);
            $table->timestamp('quota_exhausted_at')->nullable();
            $table->timestamp('last_switched_at')->nullable();
            $table->string('last_switch_reason', 32)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_enabled']);
        });

        Schema::table('apibara_request_logs', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('auction_api_providers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('apibara_request_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_id');
        });

        Schema::dropIfExists('auction_api_providers');
    }
};
