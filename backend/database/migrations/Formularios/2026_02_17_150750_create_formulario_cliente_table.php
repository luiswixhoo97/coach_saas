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
        Schema::create('formulario_cliente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')
                ->constrained('formularios')
                ->onDelete('cascade');
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->onDelete('cascade');
            $table->boolean('obligatorio')
                ->default(true);
            $table->timestamp('fecha_asignacion')
                ->useCurrent();
            $table->timestamps();
            
            // Índices
            $table->unique(['formulario_id', 'cliente_id']);
            $table->index('cliente_id');
            $table->index('formulario_id');
            $table->index('obligatorio');
            $table->index(['cliente_id', 'obligatorio']); // Composite para queries frecuentes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formulario_cliente');
    }
};
