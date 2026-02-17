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
        Schema::create('parametros_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('parametro_id')->constrained('parametros')->onDelete('cascade');
            $table->string('valor');
            $table->date('fecha');
            $table->text('notas')->nullable();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index('cliente_id');
            $table->index('parametro_id');
            $table->index('fecha');
            $table->index(['cliente_id', 'fecha']); // Composite para historial por cliente
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_cliente');
    }
};
