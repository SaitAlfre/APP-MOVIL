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
        Schema::table('tokens_movil', function (Blueprint $table) {
            $table->timestamp('refresh_expira_en')->nullable();
        });
        Schema::create('refresh_tokens_movil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('token_movil_id')->constrained('tokens_movil')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('usado_en')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens_movil');
        Schema::table('tokens_movil', function (Blueprint $table) {
            $table->dropColumn('refresh_expira_en');
        });
    }
};
