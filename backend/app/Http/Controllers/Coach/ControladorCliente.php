<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\ActualizarClienteRequest;
use App\Http\Requests\Coach\AlmacenarClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Http\Resources\PaginacionCollection;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ControladorCliente extends Controller
{
    /**
     * Obtener coach del request.
     */
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    /**
     * Listar clientes del coach.
     */
    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $query = Cliente::with('usuario')
            ->where('creado_por', $coach->id);

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('buscar') && trim($request->buscar) !== '') {
            $buscar = '%' . trim($request->buscar) . '%';
            $query->where(function ($q) use ($buscar) {
                $q->whereHas('usuario', fn($uq) => $uq->where('email', 'like', $buscar))
                    ->orWhere('nombre', 'like', $buscar)
                    ->orWhere('apellido_paterno', 'like', $buscar)
                    ->orWhere('apellido_materno', 'like', $buscar);
            });
        }

        $clientes = $query->orderBy('created_at', 'desc')->paginate(15);

        return new PaginacionCollection($clientes, ClienteResource::class);
    }

    /**
     * Crear nuevo cliente.
     */
    public function almacenar(AlmacenarClienteRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);
        $password = $request->password ?? Str::random(12);

        $cliente = DB::transaction(function () use ($request, $coach, $password) {
            $usuario = User::create([
                'email' => $request->email,
                'password' => Hash::make($password),
                'rol' => 'cliente',
                'activo' => true,
            ]);

            return Cliente::create([
                'usuario_id' => $usuario->id,
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'sexo' => $request->sexo,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'altura' => $request->altura,
                'objetivo' => $request->objetivo,
                'activo' => true,
                'creado_por' => $coach->id,
            ]);
        });

        $cliente->load('usuario');

        return response()->json([
            'mensaje' => 'Cliente creado correctamente.',
            'datos' => new ClienteResource($cliente),
            'credenciales' => [
                'email' => $request->email,
                'password' => $password,
            ],
        ], 201);
    }

    /**
     * Mostrar detalle de un cliente.
     */
    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $cliente = Cliente::with(['usuario', 'suscripciones.plan'])
            ->where('creado_por', $coach->id)
            ->findOrFail($id);

        return response()->json([
            'datos' => new ClienteResource($cliente),
        ]);
    }

    /**
     * Actualizar cliente.
     */
    public function actualizar(ActualizarClienteRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $cliente = Cliente::where('creado_por', $coach->id)->findOrFail($id);

        $cliente->update($request->only(['nombre', 'apellido_paterno', 'apellido_materno', 'sexo', 'fecha_nacimiento', 'altura', 'objetivo']));

        return response()->json([
            'mensaje' => 'Cliente actualizado correctamente.',
            'datos' => new ClienteResource($cliente),
        ]);
    }

    /**
     * Desactivar cliente (soft delete).
     */
    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $cliente = Cliente::where('creado_por', $coach->id)->findOrFail($id);

        DB::transaction(function () use ($cliente) {
            $cliente->update(['activo' => false]);
            $cliente->usuario->update(['activo' => false]);
        });

        return response()->json([
            'mensaje' => 'Cliente desactivado correctamente.',
        ]);
    }

    /**
     * Historial completo del cliente.
     */
    public function historial(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $cliente = Cliente::with([
            'usuario',
            'suscripciones.plan',
            'suscripciones.evaluaciones',
            'rutinasAsignadas.rutina',
        ])
            ->where('creado_por', $coach->id)
            ->findOrFail($id);

        return response()->json([
            'datos' => [
                'cliente' => new ClienteResource($cliente),
                'suscripciones' => $cliente->suscripciones->map(fn($s) => [
                    'id' => $s->id,
                    'plan' => $s->plan->nombre,
                    'estado' => $s->estado,
                    'fecha_inicio' => $s->fecha_inicio->format('Y-m-d'),
                    'fecha_fin' => $s->fecha_fin->format('Y-m-d'),
                ]),
                'rutinas' => $cliente->rutinasAsignadas->map(fn($ra) => [
                    'id' => $ra->rutina->id,
                    'nombre' => $ra->rutina->nombre,
                    'asignado_el' => $ra->created_at->format('Y-m-d'),
                ]),
            ],
        ]);
    }

    /**
     * Progreso del cliente (evaluaciones).
     */
    public function progreso(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $cliente = Cliente::where('creado_por', $coach->id)->findOrFail($id);

        $evaluaciones = $cliente->suscripciones()
            ->with('evaluaciones.parametrosEvaluacion.parametro')
            ->get()
            ->flatMap(fn($s) => $s->evaluaciones)
            ->sortBy('fecha');

        return response()->json([
            'datos' => $evaluaciones->map(fn($e) => [
                'id' => $e->id,
                'fecha' => $e->fecha->format('Y-m-d'),
                'modo' => $e->modo,
                'parametros' => $e->parametrosEvaluacion->map(fn($pe) => [
                    'nombre' => $pe->parametro->nombre,
                    'valor' => $pe->valor,
                    'unidad' => $pe->parametro->unidad_medida,
                ]),
            ]),
        ]);
    }
}
