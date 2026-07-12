<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('image_transfer_jobs', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('container_number')->constrained()->nullOnDelete();
            $table->string('stage', 32)->nullable()->after('vehicle_id');
            $table->index(['vehicle_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('image_transfer_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropColumn('stage');
        });
    }
};
