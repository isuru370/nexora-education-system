<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_halls', function (Blueprint $table) {
            $table->id();

            // 🔥 Branch ownership (hall එකක් එක branch එකකට)
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->string('code', 50); // hall code (branch-scoped unique)
            $table->string('hall_name', 150);

            $table->string('hall_type', 50)->nullable(); // AC / Non-AC / Online etc

            $table->decimal('hall_price', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // 🔥 Branch-scoped unique: same code branch එකක් ඇතුලේ unique
            $table->unique(
                ['branch_id', 'code'],
                'ch_branch_code_unique'
            );

            // 🔥 Composite indexes (branch-based queries fast)
            $table->index(
                ['branch_id', 'is_active'],
                'ch_branch_active_idx'
            );

            $table->index(
                ['branch_id', 'hall_name'],
                'ch_branch_name_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_halls');
    }
};