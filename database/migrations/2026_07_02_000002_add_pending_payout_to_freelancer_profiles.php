<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('freelancer_profiles', 'pending_payout')) {
            return;
        }

        Schema::table('freelancer_profiles', function (Blueprint $table) {
            $table->decimal('pending_payout', 12, 2)->default(0)->after('total_earnings');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('freelancer_profiles', 'pending_payout')) {
            Schema::table('freelancer_profiles', function (Blueprint $table) {
                $table->dropColumn('pending_payout');
            });
        }
    }
};
