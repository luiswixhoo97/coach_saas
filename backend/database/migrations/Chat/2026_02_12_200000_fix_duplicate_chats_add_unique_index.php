<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fusionar chats duplicados y agregar índice único.
     * 
     * Problema: podían crearse múltiples chats para el mismo par coach-cliente
     * porque no había restricción de unicidad. Esto causaba que los mensajes
     * del cliente fueran a un chat diferente al que ve el coach.
     */
    public function up(): void
    {
        // Paso 1: Encontrar pares coach-cliente con chats duplicados
        $duplicados = DB::table('chats')
            ->select('coach_id', 'cliente_id', DB::raw('MIN(id) as chat_principal'))
            ->groupBy('coach_id', 'cliente_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicados as $duplicado) {
            $chatPrincipalId = $duplicado->chat_principal;

            // Obtener IDs de chats duplicados (todos excepto el principal)
            $chatsDuplicados = DB::table('chats')
                ->where('coach_id', $duplicado->coach_id)
                ->where('cliente_id', $duplicado->cliente_id)
                ->where('id', '!=', $chatPrincipalId)
                ->pluck('id');

            if ($chatsDuplicados->isEmpty()) {
                continue;
            }

            // Paso 2: Mover todos los mensajes de los chats duplicados al chat principal
            DB::table('mensajes')
                ->whereIn('chat_id', $chatsDuplicados)
                ->update(['chat_id' => $chatPrincipalId]);

            // Paso 3: Mover archivos de mensajes si hubiera carpetas de storage
            // (Los archivos están asociados a mensajes, no a chats directamente,
            //  así que solo necesitamos mover los mensajes)

            // Paso 4: Actualizar el updated_at del chat principal al más reciente
            $ultimoUpdatedAt = DB::table('chats')
                ->where('coach_id', $duplicado->coach_id)
                ->where('cliente_id', $duplicado->cliente_id)
                ->max('updated_at');

            DB::table('chats')
                ->where('id', $chatPrincipalId)
                ->update(['updated_at' => $ultimoUpdatedAt]);

            // Paso 5: Eliminar chats duplicados
            DB::table('chats')
                ->whereIn('id', $chatsDuplicados)
                ->delete();
        }

        // Paso 6: Agregar índice único para prevenir duplicados futuros
        Schema::table('chats', function (Blueprint $table) {
            $table->unique(['coach_id', 'cliente_id'], 'chats_coach_cliente_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropUnique('chats_coach_cliente_unique');
        });
    }
};

