<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('titles')) {
            Schema::create('titles', function (Blueprint $table) {
                $table->id();
                $table->string('title_key')->unique();
                $table->string('title_label');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('user_titles', 'title_id')) {
            Schema::table('user_titles', function (Blueprint $table) {
                $table->foreignId('title_id')->nullable()->constrained('titles')->cascadeOnDelete();
            });
        }

        DB::table('user_titles')->whereNull('title_id')->orderBy('id')->get()->each(function ($award): void {
            DB::table('titles')->insertOrIgnore([
                'title_key' => $award->title_key,
                'title_label' => $award->title_label ?? $award->title_key,
                'created_at' => $award->created_at ?? now(),
                'updated_at' => $award->updated_at ?? now(),
            ]);

            $titleId = DB::table('titles')->where('title_key', $award->title_key)->value('id');
            DB::table('user_titles')->where('id', $award->id)->update(['title_id' => $titleId]);
        });

        Schema::table('user_titles', function (Blueprint $table) {
            $table->unique(['user_id', 'title_id']);
            $table->dropUnique('user_titles_user_id_title_key_unique');
            $table->dropColumn(['title_key', 'title_label']);
        });
    }

    public function down(): void
    {
        Schema::table('user_titles', function (Blueprint $table) {
            $table->string('title_key')->nullable();
            $table->string('title_label')->nullable();
        });

        DB::table('user_titles')->orderBy('id')->get()->each(function ($award): void {
            $title = DB::table('titles')->where('id', $award->title_id)->first();
            if ($title) {
                DB::table('user_titles')->where('id', $award->id)->update([
                    'title_key' => $title->title_key,
                    'title_label' => $title->title_label,
                ]);
            }
        });

        Schema::table('user_titles', function (Blueprint $table) {
            $table->dropUnique('user_titles_user_id_title_id_unique');
            $table->dropForeign(['title_id']);
            $table->dropColumn('title_id');
            $table->unique(['user_id', 'title_key']);
        });

        Schema::dropIfExists('titles');
    }
};
