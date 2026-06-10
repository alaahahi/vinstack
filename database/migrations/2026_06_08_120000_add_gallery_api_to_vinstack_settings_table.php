<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->string('gallery_api_base_url')->nullable()->after('api_token');
            $table->text('gallery_api_token')->nullable()->after('gallery_api_base_url');
            $table->boolean('gallery_token_expired')->default(false)->after('gallery_api_token');
            $table->timestamp('gallery_token_checked_at')->nullable()->after('gallery_token_expired');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn([
                'gallery_api_base_url',
                'gallery_api_token',
                'gallery_token_expired',
                'gallery_token_checked_at',
            ]);
        });
    }
};
