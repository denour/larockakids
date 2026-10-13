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
        Schema::table('kids', function (Blueprint $table) {
            // Fecha del cumpleaños que ya se felicitó, para no repetirlo en la
            // segunda reunión del día ni el domingo siguiente.
            $table->date('birthday_greeted_on')->nullable()->after('birth_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kids', function (Blueprint $table) {
            $table->dropColumn('birthday_greeted_on');
        });
    }
};
