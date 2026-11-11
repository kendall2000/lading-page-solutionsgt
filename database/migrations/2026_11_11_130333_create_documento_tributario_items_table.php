<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir campos en documentos_tributarios para soportar facturación libre/manual sin pago_id obligatorio
        Schema::table('documentos_tributarios', function (Blueprint $table) {
            $table->string('token_publico', 64)->nullable()->unique()->after('numero_interno');
            $table->string('metodo_pago', 50)->default('transferencia')->after('moneda'); // transferencia, efectivo, tarjeta, paypal, cheque
            $table->string('condicion_pago', 30)->default('contado')->after('metodo_pago'); // contado, credito, renta, suscripcion
            $table->text('notas')->nullable()->after('error_mensaje');
        });

        // Tabla de ítems/renglones detallados de cada factura libre
        Schema::create('documento_tributario_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos_tributarios')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_linea')->default(1);
            $table->char('tipo', 1)->default('S'); // S = Servicio, B = Bien
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2)->default(1.00);
            $table->string('unidad_medida', 10)->default('UND');
            $table->decimal('precio_unitario', 12, 2)->default(0.00);
            $table->decimal('descuento', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_tributario_items');
        Schema::table('documentos_tributarios', function (Blueprint $table) {
            $table->dropColumn(['token_publico', 'metodo_pago', 'condicion_pago', 'notas']);
        });
    }
};
