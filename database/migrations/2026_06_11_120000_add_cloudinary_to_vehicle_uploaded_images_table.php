<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_uploaded_images', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
            $table->string('cloudinary_url', 1024)->nullable()->after('path');
            $table->string('public_id', 512)->nullable()->after('cloudinary_url');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_uploaded_images', function (Blueprint $table) {
            $table->dropColumn(['cloudinary_url', 'public_id']);
            $table->string('path')->nullable(false)->change();
        });
    }
};
