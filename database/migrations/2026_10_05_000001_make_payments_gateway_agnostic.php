<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Payments can now go through more than one gateway (Razorpay, Cashfree).
 *  - razorpay_order_id   -> gateway_order_id   (still UNIQUE)
 *  - razorpay_payment_id -> gateway_payment_id
 *  - new `gateway` column; every existing row was a Razorpay payment, so it defaults to 'razorpay'.
 *
 * Raw ALTERs (like the earlier messages-type migration) so it works on older MariaDB too.
 * Run this BEFORE deploying the code that reads the new column names.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payments CHANGE razorpay_order_id gateway_order_id VARCHAR(60) NOT NULL');
        DB::statement('ALTER TABLE payments CHANGE razorpay_payment_id gateway_payment_id VARCHAR(60) NULL');
        DB::statement("ALTER TABLE payments ADD COLUMN gateway VARCHAR(20) NOT NULL DEFAULT 'razorpay' AFTER freelancer_id");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP COLUMN gateway');
        DB::statement('ALTER TABLE payments CHANGE gateway_payment_id razorpay_payment_id VARCHAR(60) NULL');
        DB::statement('ALTER TABLE payments CHANGE gateway_order_id razorpay_order_id VARCHAR(60) NOT NULL');
    }
};
