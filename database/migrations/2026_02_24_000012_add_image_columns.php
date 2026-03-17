<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageColumns extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
}
