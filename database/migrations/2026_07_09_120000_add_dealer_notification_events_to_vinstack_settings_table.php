<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->json('dealer_notification_events')->nullable()->after('wa_queue_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn('dealer_notification_events');
        });
    }
};
