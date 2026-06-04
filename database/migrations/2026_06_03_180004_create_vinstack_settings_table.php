<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinstack_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_base_url')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinstack_settings');
    }
};
