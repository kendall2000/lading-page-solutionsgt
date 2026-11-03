<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de cotizaciones
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 30)->unique(); // Ej: COT-2026-001
            $table->date('fecha');
            $table->unsignedSmallInteger('vigencia_dias')->default(30);

            // Relación opcional con cliente / cuenta / sistema registrado
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('sistema_id')->nullable()->constrained('sistemas')->nullOnDelete();

            // Datos del Cliente (congelados en la cotización)
            $table->string('cliente_empresa', 150);
            $table->string('cliente_atencion', 120)->nullable();
            $table->string('cliente_correo', 150)->nullable();
            $table->string('cliente_telefono', 30)->nullable();

            // Datos del Proveedor (por defecto Kendall Moreno / Solutions GT)
            $table->string('proveedor_nombre', 120)->default('Kendall Moreno');
            $table->string('proveedor_cargo', 120)->default('Desarrollo y soporte de software');
            $table->string('proveedor_correo', 150)->nullable();
            $table->string('proveedor_telefono', 30)->nullable();

            // Cabecera / Título
            $table->string('titulo', 200)->default('Cotización de Soporte y Mantenimiento');
            $table->string('subtitulo', 250)->nullable(); // Ej: Sistema web Lunea Boutique: tienda en línea y panel administrativo

            // 1. Objeto de la cotización
            $table->text('objeto_descripcion')->nullable(); // Texto introductorio
            $table->json('objeto_puntos')->nullable(); // Lista de viñetas (ej: Tienda en línea, POS, Productos...)
            $table->text('objeto_nota')->nullable(); // Nota al pie del objeto

            // Tipo de cotización: 'planes' (tabla comparativa tipo Lunea) o 'items' (desglose tradicional de ítems/módulos)
            $table->string('tipo', 20)->default('planes'); // planes | items

            // 2. Planes mensuales o ítems
            // En formato 'planes': JSON con columnas de planes (ej: Básico Q300, Mantenimiento Q600, Plus Q750) y filas de características con sus valores
            // En formato 'items': JSON o filas con descripción, cantidad, precio unitario y total
            $table->json('planes')->nullable();
            $table->string('planes_nota', 500)->nullable(); // "¿Cuál elegir? El plan Básico mantiene..."

            // Moneda y montos
            $table->char('moneda', 3)->default('GTQ'); // GTQ | USD
            $table->decimal('total', 10, 2)->nullable(); // Para cotizaciones tipo 'items'

            // 3. Qué es mantenimiento y qué es desarrollo nuevo (para soporte)
            $table->json('mantenimiento_incluye')->nullable(); // array de strings
            $table->json('desarrollo_nuevo_incluye')->nullable(); // array de strings

            // 4. Módulos nuevos y mejoras
            $table->text('seccion_modulos_nuevos')->nullable();

            // 5. Condiciones comerciales
            $table->json('condiciones')->nullable(); // array de puntos / condiciones

            // 6. Aceptación y firmas
            $table->json('opciones_aceptacion')->nullable(); // Opciones de casillas de verificación para aceptar
            $table->string('firma_cliente_nombre', 150)->nullable();
            $table->string('firma_proveedor_nombre', 120)->default('Kendall Moreno');

            // Estado y control
            $table->string('estado', 20)->default('borrador'); // borrador | enviada | aceptada | rechazada | vencida
            $table->timestamp('enviada_en')->nullable();
            $table->timestamp('aceptada_en')->nullable();
            $table->text('notas_internas')->nullable();

            $table->timestamps();
        });

        // Tabla opcional para ítems individuales detallados (para cotizaciones tipo desarrollo/proyecto)
        Schema::create('cotizacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->string('descripcion', 255);
            $table->text('detalle')->nullable();
            $table->decimal('cantidad', 8, 2)->default(1);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');
    }
};
