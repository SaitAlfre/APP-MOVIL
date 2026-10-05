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
        Schema::table('rutas', function (Blueprint $table) {
            $table->foreignId('acopiador_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->json('paradas')->nullable();
            $table->json('trazado')->nullable();
            $table->text('indicaciones')->nullable();
            $table->unsignedInteger('mapa_version')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rutas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acopiador_id');
            $table->dropColumn(['paradas', 'trazado', 'indicaciones', 'mapa_version']);
        });
    }
};
