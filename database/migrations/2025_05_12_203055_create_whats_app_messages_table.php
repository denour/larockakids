<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whats_app_messages', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique()->comment('Identificador único para el tipo de mensaje');
            $table->string('name')->comment('Nombre descriptivo del mensaje');
            $table->text('message')->comment('Contenido del mensaje');
            $table->text('description')->nullable()->comment('Descripción de cuándo se debe enviar este mensaje');
            $table->boolean('is_active')->default(true)->comment('Indica si el mensaje está activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whats_app_messages');
    }
};
