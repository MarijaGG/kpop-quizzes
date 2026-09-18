<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_titles', function (Blueprint $table) {
            $table->string('title_label')->nullable()->after('title_key');
        });
    }

    public function down(): void
    {
        Schema::table('user_titles', function (Blueprint $table) {
            $table->dropColumn('title_label');
        });
    }
};