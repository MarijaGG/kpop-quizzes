<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guess_idol_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('member_id');
            $table->string('difficulty');
            $table->string('image');
            $table->timestamps();
            $table->index(['group_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guess_idol_images');
    }
};
