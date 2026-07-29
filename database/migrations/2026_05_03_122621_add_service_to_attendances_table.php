<?php

use App\Enums\ServiceTime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('service')->default(ServiceTime::First->value)->after('check_in_ip');
        });

        // Backfill existing records based on check_in hour
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $hourExpr = "cast(strftime('%H', check_in) as integer)";
        } elseif ($driver === 'pgsql') {
            $hourExpr = 'EXTRACT(HOUR FROM check_in)::integer';
        } else {
            $hourExpr = 'HOUR(check_in)';
        }

        DB::table('attendances')
            ->whereNotNull('check_in')
            ->update([
                'service' => DB::raw("CASE WHEN {$hourExpr} >= 13 THEN 'second' ELSE 'first' END"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('service');
        });
    }
};
