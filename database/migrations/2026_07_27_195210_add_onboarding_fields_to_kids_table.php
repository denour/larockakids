<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kids', function (Blueprint $table) {
            $table->string('grade_level')->nullable()->after('gender');
            $table->string('classroom')->nullable()->after('grade_level');
            $table->string('school_cycle')->nullable()->after('classroom');
            $table->string('medical_conditions')->nullable()->after('medical_notes');
            $table->string('medications')->nullable()->after('medical_conditions');
            $table->string('sphincter_control')->nullable()->after('medications');
            $table->string('nap')->nullable()->after('sphincter_control');
            $table->string('routine_notes')->nullable()->after('nap');
            $table->boolean('wants_parents_group')->default(false)->after('routine_notes');
            $table->string('notification_channel')->default('screen')->after('wants_parents_group');
        });
    }

    public function down(): void
    {
        Schema::table('kids', function (Blueprint $table) {
            $table->dropColumn([
                'grade_level',
                'classroom',
                'school_cycle',
                'medical_conditions',
                'medications',
                'sphincter_control',
                'nap',
                'routine_notes',
                'wants_parents_group',
                'notification_channel',
            ]);
        });
    }
};
