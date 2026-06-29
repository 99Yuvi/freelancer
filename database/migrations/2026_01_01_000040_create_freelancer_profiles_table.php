<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline', 120)->nullable();
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->enum('availability', ['available', 'busy', 'unavailable'])->default('available');
            $table->enum('verification_status', ['unsubmitted', 'pending', 'approved', 'rejected'])->default('unsubmitted');
            $table->text('verification_notes')->nullable();
            $table->string('resume_path', 500)->nullable();
            $table->decimal('total_earnings', 14, 2)->default(0.00);
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
            $table->index('availability');
            $table->index('verification_status');
            $table->index('rating_avg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profiles');
    }
};
