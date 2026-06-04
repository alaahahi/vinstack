<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('source', 20)->default('vinstack')->after('id')->index();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['vinstack_id']);
            $table->string('vinstack_id')->nullable()->change();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unique('vin');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['vin']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('vinstack_id')->nullable(false)->change();
            $table->unique('vinstack_id');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
