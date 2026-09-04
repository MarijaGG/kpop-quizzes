<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->string('quiz_name')->nullable()->after('quiz_id');
            $table->string('result_type')->nullable()->after('quiz_name');
            $table->unsignedSmallInteger('correct_answers')->nullable()->after('result_type');
            $table->unsignedSmallInteger('total_questions')->nullable()->after('correct_answers');
        });

        DB::table('quiz_results')->orderBy('id')->get()->each(function ($result): void {
            $details = json_decode($result->details ?? '{}', true) ?? [];
            $resultType = ($details['quiz_type'] ?? null) === 'percent' ? 'knowledge' : 'personality';
            $percentage = $details['percentage'] ?? null;

            DB::table('quiz_results')->where('id', $result->id)->update([
                'quiz_name' => $details['quiz_name'] ?? 'Quiz',
                'result_type' => $resultType,
                'correct_answers' => $resultType === 'knowledge' ? ($details['correct'] ?? null) : null,
                'total_questions' => $resultType === 'knowledge' ? ($details['total'] ?? null) : null,
                'result_name' => $resultType === 'knowledge' ? null : $result->result_name,
                'total_points' => $resultType === 'knowledge' ? ($percentage ?? 0) : 0,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_results', function (Blueprint $table) {
            $table->dropColumn(['quiz_name', 'result_type', 'correct_answers', 'total_questions']);
        });
    }
};
