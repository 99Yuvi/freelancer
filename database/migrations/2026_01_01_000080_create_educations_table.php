<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('institution', 150);
            $table->string('degree', 100);
            $table->string('field_of_study', 100)->nullable();
            $table->year('start_year');
            $table->year('end_year')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educations');
    }
};
