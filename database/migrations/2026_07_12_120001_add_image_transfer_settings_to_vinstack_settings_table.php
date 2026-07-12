<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->boolean('image_transfer_async_enabled')->default(true)->after('cloudinary_folder');
            $table->unsignedSmallInteger('image_transfer_batch_size')->default(10)->after('image_transfer_async_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn(['image_transfer_async_enabled', 'image_transfer_batch_size']);
        });
    }
};
