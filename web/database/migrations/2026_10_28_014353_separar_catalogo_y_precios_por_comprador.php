<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('litros_por_unidad', 12, 3)->nullable()->change();
            $table->boolean('receta_activa')->default(true);
            $table->decimal('precio_proveedor', 12, 2)->nullable();
            $table->decimal('precio_cliente', 12, 2)->nullable();
            $table->decimal('precio_mayorista', 12, 2)->nullable();
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_id')->nullable()->change();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->restrictOnDelete();
            $table->string('tipo_comprador', 20)->default('cliente');
        });
        DB::table('ventas')->whereIn('cliente_id', DB::table('clientes')->select('id')->where('tipo', 'mayorista'))
            ->update(['tipo_comprador' => 'mayorista']);
    }

    public function down(): void
    {
        if (DB::table('productos')->whereNull('litros_por_unidad')->exists() || DB::table('ventas')->whereNotNull('proveedor_id')->exists()) {
            throw new RuntimeException('No se puede revertir mientras existan productos sin receta o ventas a proveedores.');
        }
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proveedor_id');
            $table->dropColumn('tipo_comprador');
            $table->unsignedBigInteger('cliente_id')->nullable(false)->change();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['receta_activa', 'precio_proveedor', 'precio_cliente', 'precio_mayorista']);
            $table->decimal('litros_por_unidad', 12, 3)->nullable(false)->change();
        });
    }
};
