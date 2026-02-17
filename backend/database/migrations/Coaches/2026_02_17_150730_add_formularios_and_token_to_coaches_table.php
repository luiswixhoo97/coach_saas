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
        Schema::table('coaches', function (Blueprint $table) {
            $table->foreignId('formulario_registro_id')
                ->nullable()
                ->after('activo')
                ->constrained('formularios')
                ->onDelete('set null');
            
            $table->foreignId('formulario_inicial_id')
                ->nullable()
                ->after('formulario_registro_id')
                ->constrained('formularios')
                ->onDelete('set null');
            
            $table->string('token_registro', 64)
                ->unique()
                ->nullable()
                ->after('formulario_inicial_id');
            
            $table->boolean('link_registro_activo')
                ->default(true)
                ->after('token_registro');
            
            // Índices
            $table->index('formulario_registro_id');
            $table->index('formulario_inicial_id');
            $table->index('token_registro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropForeign(['formulario_registro_id']);
            $table->dropForeign(['formulario_inicial_id']);
            $table->dropIndex(['formulario_registro_id']);
            $table->dropIndex(['formulario_inicial_id']);
            $table->dropIndex(['token_registro']);
            $table->dropColumn([
                'formulario_registro_id',
                'formulario_inicial_id',
                'token_registro',
                'link_registro_activo',
            ]);
        });
    }
};
