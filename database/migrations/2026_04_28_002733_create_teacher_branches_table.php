<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeacherBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('teacher_branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['teacher_id', 'branch_id'],
                'teacher_branch_unique'
            );

            $table->index(['branch_id', 'teacher_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('teacher_branches');
    }
}