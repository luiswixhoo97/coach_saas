<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Cliente;
use App\Models\Coach;
use Illuminate\Console\Command;

class VerificarRelacionesChat extends Command
{
    protected $signature = 'chat:verificar-relaciones';
    protected $description = 'Verifica y corrige las relaciones de chat entre coaches y clientes';

    public function handle()
    {
        $this->info('Verificando relaciones de chat...');

        // Verificar clientes con creado_por incorrecto
        $clientes = Cliente::with('coach')->get();
        $problemas = [];

        foreach ($clientes as $cliente) {
            // Verificar si creado_por apunta a un coach válido
            if ($cliente->creado_por) {
                $coach = Coach::find($cliente->creado_por);
                if (!$coach) {
                    $problemas[] = [
                        'tipo' => 'cliente_sin_coach',
                        'cliente_id' => $cliente->id,
                        'cliente_nombre' => $cliente->nombre,
                        'creado_por' => $cliente->creado_por,
                        'problema' => 'creado_por no apunta a un coach válido'
                    ];
                } else {
                    // Verificar si el usuario_id del coach coincide con creado_por (incorrecto)
                    if ($cliente->creado_por == $coach->usuario_id) {
                        $problemas[] = [
                            'tipo' => 'cliente_con_usuario_id',
                            'cliente_id' => $cliente->id,
                            'cliente_nombre' => $cliente->nombre,
                            'creado_por_actual' => $cliente->creado_por,
                            'coach_id_correcto' => $coach->id,
                            'coach_usuario_id' => $coach->usuario_id,
                            'problema' => 'creado_por tiene el usuario_id del coach en lugar del id'
                        ];
                    }
                }
            }
        }

        // Verificar chats con coach_id incorrecto
        $chats = Chat::with(['coach', 'cliente'])->get();
        foreach ($chats as $chat) {
            $coach = Coach::find($chat->coach_id);
            if (!$coach) {
                $problemas[] = [
                    'tipo' => 'chat_sin_coach',
                    'chat_id' => $chat->id,
                    'coach_id' => $chat->coach_id,
                    'cliente_id' => $chat->cliente_id,
                    'problema' => 'coach_id no apunta a un coach válido'
                ];
            } else {
                // Verificar si el coach_id es el usuario_id en lugar del id
                $cliente = $chat->cliente;
                if ($cliente && $cliente->creado_por) {
                    $coachCorrecto = Coach::find($cliente->creado_por);
                    if ($coachCorrecto && $chat->coach_id != $coachCorrecto->id) {
                        $problemas[] = [
                            'tipo' => 'chat_coach_incorrecto',
                            'chat_id' => $chat->id,
                            'coach_id_actual' => $chat->coach_id,
                            'coach_id_correcto' => $coachCorrecto->id,
                            'cliente_id' => $chat->cliente_id,
                            'problema' => 'El coach_id del chat no coincide con el creado_por del cliente'
                        ];
                    }
                }
            }
        }

        if (empty($problemas)) {
            $this->info('✓ Todas las relaciones están correctas.');
            return 0;
        }

        $this->warn('Se encontraron ' . count($problemas) . ' problemas:');
        $this->table(
            ['Tipo', 'ID', 'Problema'],
            array_map(function ($p) {
                return [
                    $p['tipo'],
                    $p['cliente_id'] ?? $p['chat_id'] ?? 'N/A',
                    $p['problema']
                ];
            }, $problemas)
        );

        if ($this->confirm('¿Deseas corregir estos problemas automáticamente?', false)) {
            $this->corregirProblemas($problemas);
        }

        return 0;
    }

    private function corregirProblemas(array $problemas)
    {
        $this->info('Corrigiendo problemas...');

        foreach ($problemas as $problema) {
            try {
                switch ($problema['tipo']) {
                    case 'cliente_con_usuario_id':
                        $cliente = Cliente::find($problema['cliente_id']);
                        if ($cliente) {
                            $cliente->creado_por = $problema['coach_id_correcto'];
                            $cliente->save();
                            $this->info("✓ Corregido cliente {$cliente->id}: creado_por = {$problema['coach_id_correcto']}");
                        }
                        break;

                    case 'chat_coach_incorrecto':
                        $chat = Chat::find($problema['chat_id']);
                        if ($chat) {
                            $chat->coach_id = $problema['coach_id_correcto'];
                            $chat->save();
                            $this->info("✓ Corregido chat {$chat->id}: coach_id = {$problema['coach_id_correcto']}");
                        }
                        break;
                }
            } catch (\Exception $e) {
                $this->error("Error al corregir {$problema['tipo']}: " . $e->getMessage());
            }
        }

        $this->info('Corrección completada.');
    }
}




