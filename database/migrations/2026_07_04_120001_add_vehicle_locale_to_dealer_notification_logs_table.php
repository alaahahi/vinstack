<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dealer_notification_logs', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('dealer_id')->constrained()->nullOnDelete();
            $table->string('locale', 8)->nullable()->after('event');
        });
    }

    public function down(): void
    {
        Schema::table('dealer_notification_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropColumn('locale');
        });
    }
};
