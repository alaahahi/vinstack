<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->string('cloudinary_cloud_name')->nullable()->after('vehicle_options');
            $table->string('cloudinary_api_key')->nullable()->after('cloudinary_cloud_name');
            $table->text('cloudinary_api_secret')->nullable()->after('cloudinary_api_key');
            $table->string('cloudinary_upload_preset')->nullable()->after('cloudinary_api_secret');
            $table->string('cloudinary_folder')->nullable()->after('cloudinary_upload_preset');
        });
    }

    public function down(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cloudinary_cloud_name',
                'cloudinary_api_key',
                'cloudinary_api_secret',
                'cloudinary_upload_preset',
                'cloudinary_folder',
            ]);
        });
    }
};
