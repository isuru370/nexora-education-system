<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->string('code', 50)->unique();

            $table->string('address1', 150)->nullable();
            $table->string('address2', 150)->nullable();
            $table->string('address3', 150)->nullable();

            $table->string('mobile', 20)->nullable();
            $table->string('email', 150)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};