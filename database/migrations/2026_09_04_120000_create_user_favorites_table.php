<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['user_id', 'item_type', 'position']);
            $table->unique(['user_id', 'item_type', 'item_id']);
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user): void {
            $legacy = [
                'group' => $user->favorite_group_id,
                'member' => $user->favorite_member_id,
                'album' => $user->favorite_album_id,
            ];

            foreach ($legacy as $type => $itemId) {
                if ($itemId) {
                    DB::table('user_favorites')->insert([
                        'user_id' => $user->id,
                        'item_type' => $type,
                        'item_id' => $itemId,
                        'position' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['favorite_group_id', 'favorite_member_id', 'favorite_album_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('favorite_group_id')->nullable()->after('avatar_member_id');
            $table->unsignedBigInteger('favorite_member_id')->nullable()->after('favorite_group_id');
            $table->unsignedBigInteger('favorite_album_id')->nullable()->after('favorite_member_id');
        });

        DB::table('user_favorites')->where('position', 1)->get()->each(function ($favorite): void {
            $column = match ($favorite->item_type) {
                'group' => 'favorite_group_id',
                'member' => 'favorite_member_id',
                'album' => 'favorite_album_id',
                default => null,
            };

            if ($column) {
                DB::table('users')->where('id', $favorite->user_id)->update([$column => $favorite->item_id]);
            }
        });

        Schema::dropIfExists('user_favorites');
    }
};
