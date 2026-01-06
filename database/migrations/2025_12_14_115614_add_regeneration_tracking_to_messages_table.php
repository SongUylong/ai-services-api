<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Add fields to track regeneration
            $table->foreignId('original_message_id')->nullable()->after('ai_model_id')->constrained('messages')->onDelete('cascade');
            $table->integer('regeneration_index')->default(0)->after('original_message_id');
            
            // Index for querying regenerated messages
            $table->index(['original_message_id', 'regeneration_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['original_message_id']);
            $table->dropIndex(['original_message_id', 'regeneration_index']);
            $table->dropColumn(['original_message_id', 'regeneration_index']);
        });
    }
};
