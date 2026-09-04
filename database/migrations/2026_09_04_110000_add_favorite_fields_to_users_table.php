<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('favorite_group_id')->nullable()->after('avatar_member_id');
            $table->unsignedBigInteger('favorite_member_id')->nullable()->after('favorite_group_id');
            $table->unsignedBigInteger('favorite_album_id')->nullable()->after('favorite_member_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['favorite_group_id', 'favorite_member_id', 'favorite_album_id']);
        });
    }
};
