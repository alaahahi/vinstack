<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_transfer_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 40);
            $table->string('status', 20)->default('queued');
            $table->string('container_number')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('replace_existing')->default(true);
            $table->unsignedInteger('total_images')->default(0);
            $table->unsignedInteger('transferred_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('staging_dir')->nullable();
            $table->json('manifest');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_transfer_jobs');
    }
};
