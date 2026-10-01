<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->uuid('attempt_key')->nullable()->unique()->after('quiz_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropUnique(['attempt_key']);
            $table->dropColumn('attempt_key');
        });
    }
};