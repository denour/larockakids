<?php

use App\Models\Kid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6)->index();
            $table->string('status')->default('pending'); // pending, matched, expired
            $table->foreignIdFor(Kid::class)->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_sessions');
    }
};
