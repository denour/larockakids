<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allergy_kid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allergy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kid_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allergy_kid');
    }
}; 