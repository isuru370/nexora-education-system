<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrganizerBranchesTable extends Migration
{
    public function up()
    {
        Schema::create('organizer_branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organizer_id')
                ->constrained('organizers')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['organizer_id', 'branch_id'],
                'organizer_branch_unique'
            );

            $table->index(['branch_id', 'organizer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('organizer_branches');
    }
}