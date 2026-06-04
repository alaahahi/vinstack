<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_uploaded_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['terminal', 'pickup', 'destination']);
            $table->string('path');
            $table->string('original_name');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_uploaded_images');
    }
};
