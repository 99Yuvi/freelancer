<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('quality');
            $table->unsignedTinyInteger('timeliness');
            $table->unsignedTinyInteger('overall');
            $table->text('body')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->boolean('is_visible')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->unique(['contract_id', 'reviewer_id']);
            $table->index(['reviewee_id', 'is_visible', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
