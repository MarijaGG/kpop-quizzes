<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep currently available quizzes published; new quizzes are explicitly drafts.
        Schema::table('quizzes', function (Blueprint $table): void {
            $table->boolean('is_published')->default(true);
        });

        // Normalize existing order values before enforcing one order per quiz.
        $quizIds = DB::table('questions')->distinct()->orderBy('quiz_id')->pluck('quiz_id');
        foreach ($quizIds as $quizId) {
            $questionIds = DB::table('questions')
                ->where('quiz_id', $quizId)
                ->orderBy('order')
                ->orderBy('id')
                ->pluck('id');

            foreach ($questionIds as $index => $questionId) {
                DB::table('questions')->where('id', $questionId)->update(['order' => $index + 1]);
            }
        }

        Schema::table('questions', function (Blueprint $table): void {
            $table->unique(['quiz_id', 'order'], 'questions_quiz_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique('questions_quiz_order_unique');
        });

        Schema::table('quizzes', function (Blueprint $table): void {
            $table->dropColumn('is_published');
        });
    }
};
