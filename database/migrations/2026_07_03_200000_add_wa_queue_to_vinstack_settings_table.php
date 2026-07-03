<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->string('wa_queue_base_url')->nullable()->after('cloudinary_folder');
            $table->unsignedBigInteger('wa_queue_sender_id')->nullable()->after('wa_queue_base_url');
            $table->boolean('wa_queue_enabled')->default(false)->after('wa_queue_sender_id');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn([
                'wa_queue_base_url',
                'wa_queue_sender_id',
                'wa_queue_enabled',
            ]);
        });
    }
};
