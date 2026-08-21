<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('identifier', 191);
            $table->string('platform', 32)->nullable();
            $table->string('vin', 32)->nullable();
            $table->string('lot_number', 64)->nullable();
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('thumb_url', 1000)->nullable();
            $table->decimal('current_bid_usd', 12, 2)->nullable();
            $table->decimal('buy_now_usd', 12, 2)->nullable();
            $table->string('location_display')->nullable();
            $table->string('primary_damage')->nullable();
            $table->string('auction_at', 64)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'identifier']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_favorites');
    }
};
