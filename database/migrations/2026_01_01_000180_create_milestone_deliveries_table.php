<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('milestone_delivery_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('milestone_deliveries')->cascadeOnDelete();
            $table->string('file_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 80);
            $table->unsignedInteger('file_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_delivery_files');
        Schema::dropIfExists('milestone_deliveries');
    }
};
