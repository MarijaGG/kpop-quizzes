<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove legacy/orphaned image prompts before enforcing their references.
        $orphanedGroupImages = DB::table('guess_idol_images')
            ->leftJoin('groups', 'groups.id', '=', 'guess_idol_images.group_id')
            ->whereNull('groups.id')
            ->pluck('guess_idol_images.id');
        DB::table('guess_idol_images')->whereIn('id', $orphanedGroupImages)->delete();

        $orphanedMemberImages = DB::table('guess_idol_images')
            ->leftJoin('members', 'members.id', '=', 'guess_idol_images.member_id')
            ->whereNull('members.id')
            ->pluck('guess_idol_images.id');
        DB::table('guess_idol_images')->whereIn('id', $orphanedMemberImages)->delete();

        // Game results used 0 as a fake quiz ID. Convert that sentinel and any
        // other orphaned references to NULL, preserving the history row.
        // The column must allow NULL before these data updates are run.
        Schema::table('quiz_results', function (Blueprint $table): void {
            $table->unsignedBigInteger('quiz_id')->nullable()->change();
        });

        DB::table('quiz_results')->where('quiz_id', 0)->update(['quiz_id' => null]);
        $orphanedQuizResults = DB::table('quiz_results')
            ->leftJoin('quizzes', 'quizzes.id', '=', 'quiz_results.quiz_id')
            ->whereNotNull('quiz_results.quiz_id')
            ->whereNull('quizzes.id')
            ->pluck('quiz_results.id');
        DB::table('quiz_results')->whereIn('id', $orphanedQuizResults)->update(['quiz_id' => null]);

        $orphanedUserResults = DB::table('quiz_results')
            ->leftJoin('users', 'users.id', '=', 'quiz_results.user_id')
            ->whereNotNull('quiz_results.user_id')
            ->whereNull('users.id')
            ->pluck('quiz_results.id');
        DB::table('quiz_results')->whereIn('id', $orphanedUserResults)->update(['user_id' => null]);

        // MySQL may retain earlier DDL when a migration fails. Only add each
        // constraint when it is not already present so the migration can retry.
        $this->addForeignKeyIfMissing('guess_idol_images', 'group_id', 'groups', 'cascade');
        $this->addForeignKeyIfMissing('guess_idol_images', 'member_id', 'members', 'cascade');
        $this->addForeignKeyIfMissing('quiz_results', 'quiz_id', 'quizzes', 'null');
        $this->addForeignKeyIfMissing('quiz_results', 'user_id', 'users', 'null');
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table): void {
            $table->dropForeign(['quiz_id']);
            $table->dropForeign(['user_id']);
        });

        Schema::table('guess_idol_images', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['member_id']);
        });

        // Legacy game rows used zero; restore the non-null quiz_id shape.
        DB::table('quiz_results')->whereNull('quiz_id')->update(['quiz_id' => 0]);
        Schema::table('quiz_results', function (Blueprint $table): void {
            $table->unsignedBigInteger('quiz_id')->nullable(false)->change();
        });
    }

    private function addForeignKeyIfMissing(string $tableName, string $column, string $referencedTable, string $deleteAction): void
    {
        $exists = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreignKey): bool => in_array($column, $foreignKey['columns'], true));

        if ($exists) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $deleteAction): void {
            $foreign = $table->foreign($column)->references('id')->on($referencedTable);
            if ($deleteAction === 'cascade') {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->nullOnDelete();
            }
        });
    }
};
