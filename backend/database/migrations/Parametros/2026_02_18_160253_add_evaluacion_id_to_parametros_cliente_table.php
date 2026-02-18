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
        Schema::table('parametros_cliente', function (Blueprint $table) {
            $table->foreignId('evaluacion_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('evaluaciones')
                ->onDelete('set null');
            
            $table->index('evaluacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parametros_cliente', function (Blueprint $table) {
            $table->dropForeign(['evaluacion_id']);
            $table->dropIndex(['evaluacion_id']);
            $table->dropColumn('evaluacion_id');
        });
    }
};
