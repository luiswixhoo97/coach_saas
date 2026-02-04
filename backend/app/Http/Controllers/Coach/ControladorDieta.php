<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\SolicitudSubirDietaVarios;
use App\Http\Resources\Coach\DietaClienteResource;
use App\Models\Cliente;
use App\Models\DietaCliente;
use App\Models\Suscripcion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ControladorDieta extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dietas = DietaCliente::with(['suscripcion.cliente.usuario'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'datos' => $dietas->map(fn($d) => [
                'id' => $d->id,
                'archivo' => $d->archivo,
                'activo' => $d->activo,
                'cliente' => $d->suscripcion->cliente->usuario->email,
                'created_at' => $d->created_at->format('Y-m-d'),
            ]),
            'meta' => [
                'total' => $dietas->total(),
                'por_pagina' => $dietas->perPage(),
                'pagina_actual' => $dietas->currentPage(),
            ],
        ]);
    }

    public function almacenar(Request $request): JsonResponse
    {
        $request->validate([
            'suscripcion_id' => 'required|exists:suscripciones,id',
            'archivo' => 'nullable|file|mimes:pdf|max:10240',
            'archivos' => 'nullable|array|min:1',
            'archivos.*' => 'required|file|mimes:pdf|max:10240',
            'desactivar_anteriores' => 'sometimes|boolean',
        ]);

        $coach = $this->getCoach($request);

        $suscripcion = Suscripcion::whereHas('plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($request->suscripcion_id);

        // Desactivar dietas anteriores si se solicita
        if ($request->boolean('desactivar_anteriores')) {
            DietaCliente::where('suscripcion_id', $suscripcion->id)->update(['activo' => false]);
        }

        $archivos = [];
        if ($request->hasFile('archivo')) {
            $archivos[] = $request->file('archivo');
        }
        if ($request->hasFile('archivos')) {
            $archivos = array_merge($archivos, $request->file('archivos'));
        }

        if (empty($archivos)) {
            return response()->json([
                'mensaje' => 'Debes seleccionar al menos un archivo.',
            ], 422);
        }

        $dietasCreadas = [];
        foreach ($archivos as $archivo) {
            // Conservar el nombre original del archivo
            $nombreOriginal = $archivo->getClientOriginalName();
            // Sanitizar el nombre (remover caracteres problemáticos)
            $nombreSanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
            $extension = $archivo->getClientOriginalExtension();
            $nombreFinal = $nombreSanitizado . '.' . $extension;
            
            // Si el archivo ya existe, agregar timestamp
            $contador = 1;
            $nombreBase = $nombreFinal;
            while (Storage::disk('public')->exists('dietas/' . $nombreFinal)) {
                $nombreFinal = $nombreSanitizado . '_' . $contador . '.' . $extension;
                $contador++;
            }
            
            $path = $archivo->storeAs('dietas', $nombreFinal, 'public');
            $dieta = DietaCliente::create([
                'suscripcion_id' => $suscripcion->id,
                'archivo' => $path,
                'activo' => true,
            ]);
            $dietasCreadas[] = $dieta;
        }

        return response()->json([
            'mensaje' => count($dietasCreadas) === 1 ? 'Dieta subida correctamente.' : 'Dietas subidas correctamente.',
            'datos' => count($dietasCreadas) === 1 ? $dietasCreadas[0] : DietaClienteResource::collection($dietasCreadas),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::with(['suscripcion.cliente.usuario'])
            ->whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return response()->json(['datos' => $dieta]);
    }

    public function actualizar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $request->validate([
            'activo' => 'sometimes|boolean',
        ]);

        $dieta->update($request->only(['activo']));

        return response()->json([
            'mensaje' => 'Dieta actualizada correctamente.',
            'datos' => $dieta,
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        Storage::disk('public')->delete($dieta->archivo);
        $dieta->delete();

        return response()->json(['mensaje' => 'Dieta eliminada correctamente.']);
    }

    public function descargar(Request $request, int $id)
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        return Storage::disk('public')->download($dieta->archivo);
    }

    public function ver(Request $request, int $id)
    {
        $coach = $this->getCoach($request);

        $dieta = DietaCliente::whereHas('suscripcion.plan', fn($q) => $q->where('coach_id', $coach->id))
            ->findOrFail($id);

        $path = Storage::disk('public')->path($dieta->archivo);
        
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($dieta->archivo) . '"',
        ]);
    }

    /**
     * Subir dieta a varios clientes.
     */
    public function subirVarios(SolicitudSubirDietaVarios $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $clienteIds = $request->cliente_ids;
        $archivos = $request->file('archivos');

        // Verificar que todos los clientes pertenezcan al coach
        $clientes = Cliente::where('creado_por', $coach->id)
            ->whereIn('id', $clienteIds)
            ->get();

        if ($clientes->count() !== count($clienteIds)) {
            return response()->json([
                'mensaje' => 'Uno o más clientes no pertenecen a tu cuenta.',
            ], 403);
        }

        // Obtener suscripciones activas de los clientes
        $suscripciones = [];
        foreach ($clientes as $cliente) {
            $suscripcionActiva = $cliente->suscripcionActiva();
            if ($suscripcionActiva) {
                $suscripciones[$cliente->id] = $suscripcionActiva;
            }
        }

        if (empty($suscripciones)) {
            return response()->json([
                'mensaje' => 'Los clientes seleccionados no tienen suscripciones activas.',
            ], 422);
        }

        // Crear dietas: N clientes × M archivos
        // Almacenar cada archivo una vez y reutilizar la ruta para todos los clientes
        $dietasCreadas = [];
        
        DB::transaction(function () use ($suscripciones, $archivos, &$dietasCreadas) {
            // Almacenar cada archivo una vez con nombre original
            $archivosAlmacenados = [];
            foreach ($archivos as $archivo) {
                // Conservar el nombre original del archivo
                $nombreOriginal = $archivo->getClientOriginalName();
                // Sanitizar el nombre (remover caracteres problemáticos)
                $nombreSanitizado = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME));
                $extension = $archivo->getClientOriginalExtension();
                $nombreFinal = $nombreSanitizado . '.' . $extension;
                
                // Si el archivo ya existe, agregar timestamp
                $contador = 1;
                $nombreBase = $nombreFinal;
                while (Storage::disk('public')->exists('dietas/' . $nombreFinal)) {
                    $nombreFinal = $nombreSanitizado . '_' . $contador . '.' . $extension;
                    $contador++;
                }
                
                $path = $archivo->storeAs('dietas', $nombreFinal, 'public');
                $archivosAlmacenados[] = $path;
            }
            
            // Crear una entrada en dieta_cliente por cada combinación cliente × archivo
            // Reutilizamos el mismo path para todos los clientes (mismo archivo físico)
            foreach ($suscripciones as $suscripcion) {
                foreach ($archivosAlmacenados as $path) {
                    $dieta = DietaCliente::create([
                        'suscripcion_id' => $suscripcion->id,
                        'archivo' => $path,
                        'activo' => true,
                    ]);
                    $dietasCreadas[] = $dieta;
                }
            }
        });

        return response()->json([
            'mensaje' => 'Dietas subidas correctamente a ' . count($suscripciones) . ' cliente(s).',
            'datos' => [
                'clientes' => count($suscripciones),
                'archivos' => count($archivos),
                'total_dietas' => count($dietasCreadas),
            ],
        ], 201);
    }
}
