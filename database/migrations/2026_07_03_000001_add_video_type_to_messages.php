<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Blueprint cannot modify enum columns — raw statement required
        DB::statement("ALTER TABLE messages MODIFY COLUMN `type` ENUM('text','file','image','video') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        // Downgrade video rows to 'file' so the narrower enum doesn't reject them
        DB::statement("UPDATE messages SET `type` = 'file' WHERE `type` = 'video'");
        DB::statement("ALTER TABLE messages MODIFY COLUMN `type` ENUM('text','file','image') NOT NULL DEFAULT 'text'");
    }
};
