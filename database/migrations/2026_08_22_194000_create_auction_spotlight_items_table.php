<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->boolean('auction_spotlight_enabled')->default(true)->after('sync_enabled');
        });

        Schema::create('auction_spotlight_items', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 191);
            $table->string('platform', 32)->nullable();
            $table->string('vin', 32)->nullable();
            $table->string('lot_number', 64)->nullable();
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('make', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->json('thumb_urls')->nullable();
            $table->decimal('current_bid_usd', 12, 2)->nullable();
            $table->string('location_display')->nullable();
            $table->string('primary_damage')->nullable();
            $table->foreignId('last_viewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('view_count')->default(1);
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->unique('identifier');
            $table->index(['last_viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_spotlight_items');

        Schema::table('vinstack_settings', function (Blueprint $table) {
            $table->dropColumn('auction_spotlight_enabled');
        });
    }
};
