<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apibara_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_role', 32)->nullable();
            $table->string('user_name')->nullable();
            $table->string('endpoint', 191);
            $table->string('method', 16)->default('GET');
            $table->json('query')->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->boolean('cached')->default(false);
            $table->boolean('billed')->default(true);
            $table->unsignedInteger('elapsed_ms')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['billed', 'created_at']);
            $table->index(['endpoint', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apibara_request_logs');
    }
};
