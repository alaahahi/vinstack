<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autoshipper_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_base_url')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('sync_enabled')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_auto_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('autoshipper_id')->nullable()->unique()->after('vinstack_id');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['autoshipper_id']);
            $table->dropColumn('autoshipper_id');
        });

        Schema::dropIfExists('autoshipper_settings');
    }
};
