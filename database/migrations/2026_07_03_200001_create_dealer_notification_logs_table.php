<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealer_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 32);
            $table->text('message');
            $table->string('channel', 32)->default('whatsapp');
            $table->string('source', 64)->default('manual');
            $table->string('event', 128)->nullable();
            $table->unsignedBigInteger('wa_queue_id')->nullable();
            $table->string('wa_queue_status', 32)->nullable();
            $table->json('wa_queue_response')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['dealer_id', 'created_at']);
            $table->index('wa_queue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_notification_logs');
    }
};
