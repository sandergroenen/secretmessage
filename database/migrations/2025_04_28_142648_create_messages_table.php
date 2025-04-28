<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->text('content'); // Encrypted content
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes(); // For self-destructing messages
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks to allow dropping tables with foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Drop the tables regardless of constraints
        Schema::dropIfExists('message');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
