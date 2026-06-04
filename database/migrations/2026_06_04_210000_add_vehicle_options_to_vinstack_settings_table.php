<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->json('vehicle_options')->nullable()->after('support_phone');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn('vehicle_options');
        });
    }
};
