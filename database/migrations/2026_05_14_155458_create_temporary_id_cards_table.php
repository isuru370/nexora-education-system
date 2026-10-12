
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemporaryIdCardsTable extends Migration
{
    public function up()
    {
        Schema::create('temporary_id_cards', function (Blueprint $table) {
            $table->id();

            // Temporary ID number
            $table->string('temporary_id_number', 50)->unique();

            // Card number
            $table->string('card_number', 50)->unique();

            // Student
            $table->foreignId('student_id')
                ->nullable()
                ->constrained('students')
                ->nullOnDelete();

            // Card activation
            $table->timestamp('activated_at')->nullable();

            // Card status
            $table->enum('status', [
                'pending',
                'downloaded',
                'issued',
                'active',
                'expired',
            ])->default('pending');

            $table->timestamps();

            // Index
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('temporary_id_cards');
    }
}
