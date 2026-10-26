<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas_leche', function (Blueprint $table) {
            $table->id();
            $table->decimal('precio', 10, 3);
            $table->date('desde')->index();
            $table->date('hasta');
            $table->timestamps();
        });
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->json('tarifas_aplicadas')->nullable();
            $table->json('detalle_valorado')->nullable();
        });
        DB::table('configuraciones')->insertOrIgnore(['clave' => 'tarifas_version', 'valor' => '0']);
    }

    public function down(): void
    {
        Schema::table('liquidaciones', fn (Blueprint $table) => $table->dropColumn(['tarifas_aplicadas', 'detalle_valorado']));
        Schema::dropIfExists('tarifas_leche');
        DB::table('configuraciones')->where('clave', 'tarifas_version')->delete();
    }
};
