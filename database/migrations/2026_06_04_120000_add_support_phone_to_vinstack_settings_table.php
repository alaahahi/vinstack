<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->string('support_phone', 50)->nullable()->after('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn('support_phone');
        });
    }
};
