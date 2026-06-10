<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_images', function (Blueprint $table) {
            $table->id();
            $table->string('container_number')->index();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vin', 32)->nullable()->index();
            $table->string('original_name')->nullable();
            $table->string('cloudinary_url', 1024);
            $table->string('public_id', 512);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();

            $table->index(['container_number', 'vin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_images');
    }
};
