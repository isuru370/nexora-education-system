<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_cards', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Branch
            |--------------------------------------------------------------------------
            | Branch responsible for this card inventory.
            */
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Assigned Student
            |--------------------------------------------------------------------------
            | NULL means the card has not been assigned.
            */
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Card Identification
            |--------------------------------------------------------------------------
            */

            // Sequence unique within each branch.
            // Example: Branch 1 = 1-100, Branch 2 = 1-100.
            $table->unsignedBigInteger('card_sequence');

            // Global sequence used for QR code generation.
            // Example: Branch 1 = 1-100, Branch 2 = 101-200.
            $table->unsignedBigInteger('global_qr_sequence')->unique();

            // Card number is unique within its branch.
            // Example: CARD-BR01-001, CARD-BR02-001.
            $table->string('card_number', 50);

            // QR code must be globally unique.
            // Example: QR-0001, QR-0101.
            $table->string('qr_code', 100)->unique();

            /*
            |--------------------------------------------------------------------------
            | Card Status
            |--------------------------------------------------------------------------
            */
            $table->enum('status', [
                'available',
                'assigned',
                'lost',
                'damaged',
                'inactive',
            ])->default('available');

            /*
            |--------------------------------------------------------------------------
            | Assignment Information
            |--------------------------------------------------------------------------
            */
            $table->boolean('is_current')->default(false);

            $table->timestamp('issued_at')->nullable();

            $table->timestamp('deactivated_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Additional Information
            |--------------------------------------------------------------------------
            */
            $table->text('remarks')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            // Each branch can restart its card sequence from 1.
            $table->unique(
                ['branch_id', 'card_sequence'],
                'student_cards_branch_sequence_unique'
            );

            // Card numbers only need to be unique within each branch.
            $table->unique(
                ['branch_id', 'card_number'],
                'student_cards_branch_card_number_unique'
            );

            $table->index(
                ['branch_id', 'status'],
                'student_cards_branch_status_index'
            );

            $table->index('student_id');

            $table->index('status');

            $table->index(['student_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_cards');
    }
};
