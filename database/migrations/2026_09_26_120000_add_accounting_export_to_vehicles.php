<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->timestamp('exported_to_accounting_at')->nullable()->after('notes');
            $table->unsignedBigInteger('copart_car_id')->nullable()->after('exported_to_accounting_at');
            $table->string('accounting_export_status', 32)->nullable()->after('copart_car_id');
            $table->text('accounting_export_error')->nullable()->after('accounting_export_status');
        });

        Schema::create('accounting_export_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_base_url')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_export_settings');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'exported_to_accounting_at',
                'copart_car_id',
                'accounting_export_status',
                'accounting_export_error',
            ]);
        });
    }
};
