<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_calidad_id')->nullable()->unique()->constrained('controles_calidad')->nullOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->string('tipo', 30)->default('calidad');
            $table->string('severidad', 20);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->string('motivo');
            $table->string('estado', 20)->default('pendiente');
            $table->foreignId('resuelto_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('resuelto_en')->nullable();
            $table->foreignId('aplicada_liquidacion_id')->nullable()->constrained('liquidaciones')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->decimal('descuento_sanciones', 10, 2)->default(0)->after('monto_total');
        });

        Schema::create('reclamos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('entrega_id')->constrained('entregas')->restrictOnDelete();
            $table->decimal('litros_originales', 8, 2);
            $table->decimal('litros_solicitados', 8, 2);
            $table->string('motivo');
            $table->string('estado', 20)->default('pendiente');
            $table->text('respuesta')->nullable();
            $table->foreignId('resuelto_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('tipo', 30);
            $table->string('nombre');
            $table->string('documento', 20)->unique();
            $table->string('celular', 20)->nullable();
            $table->string('ciudad', 80)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('estado', 20)->default('pendiente');
            $table->timestamp('vendida_en');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 12, 3);
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->text('contenido');
            $table->string('audiencia', 30);
            $table->string('estado', 20)->default('borrador');
            $table->timestamp('publicar_en')->nullable();
            $table->timestamp('publicado_en')->nullable();
            $table->foreignId('autor_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('importaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('tipo', 30);
            $table->string('archivo_original');
            $table->string('ruta_archivo');
            $table->string('estado', 20)->default('validada');
            $table->unsignedInteger('filas_total')->default(0);
            $table->unsignedInteger('filas_procesadas')->default(0);
            $table->unsignedInteger('filas_error')->default(0);
            $table->json('vista_previa')->nullable();
            $table->json('errores')->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('procesada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('configuraciones', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->text('valor')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->foreignId('zona_id')->constrained('zonas')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('precios_litro', function (Blueprint $table) {
            $table->id();
            $table->date('vigente_desde')->unique();
            $table->decimal('precio', 10, 4);
            $table->timestamps();
        });

        Schema::create('semanas_operativas', function (Blueprint $table) {
            $table->id();
            $table->date('inicio')->unique();
            $table->date('fin');
            $table->string('estado', 20)->default('abierta');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semanas_operativas');
        Schema::dropIfExists('precios_litro');
        Schema::dropIfExists('rutas');
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('importaciones');
        Schema::dropIfExists('comunicados');
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('reclamos_proveedor');
        Schema::dropIfExists('sanciones');
        Schema::table('liquidaciones', fn (Blueprint $table) => $table->dropColumn('descuento_sanciones'));
    }
};
