<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('recovery_codes_archive')->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('recovery_codes_archived_at')->nullable()->after('recovery_codes_archive');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'recovery_codes_archive',
                'recovery_codes_archived_at',
            ]);
        });
    }
};
