<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sistemas en desarrollo: se muestran con «Próximamente» y sin demo ni prueba. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->boolean('proximamente')->default(false)->after('dias_prueba');
        });
    }

    public function down(): void
    {
        Schema::table('sistemas', function (Blueprint $table) {
            $table->dropColumn('proximamente');
        });
    }
};
