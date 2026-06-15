<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('author_role', 16);
            $table->text('body')->nullable();
            $table->string('attachment_url')->nullable();
            $table->string('attachment_public_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'created_at']);
            $table->index(['vehicle_id', 'author_role', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_messages');
    }
};
