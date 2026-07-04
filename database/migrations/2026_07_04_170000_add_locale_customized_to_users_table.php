<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('locale_customized')->default(false)->after('locale');
        });

        // Legacy default was Arabic — reset dealers who never chose a language.
        DB::table('users')
            ->where('role', 'dealer')
            ->update([
                'locale' => null,
                'locale_customized' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale_customized');
        });
    }
};
