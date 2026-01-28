<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\ActualizarPlanRequest;
use App\Http\Requests\Coach\AlmacenarPlanRequest;
use App\Http\Resources\PaginacionCollection;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorPlan extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $planes = Plan::withCount('suscripciones')
            ->where('coach_id', $coach->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return new PaginacionCollection($planes, PlanResource::class);
    }

    public function almacenar(AlmacenarPlanRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $plan = Plan::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'precio' => $request->precio,
            'duracion_dias' => $request->duracion_dias,
        ]);

        return response()->json([
            'mensaje' => 'Plan creado correctamente.',
            'datos' => new PlanResource($plan),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $plan = Plan::withCount('suscripciones')
            ->where('coach_id', $coach->id)
            ->findOrFail($id);

        return response()->json(['datos' => new PlanResource($plan)]);
    }

    public function actualizar(ActualizarPlanRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $plan = Plan::where('coach_id', $coach->id)->findOrFail($id);

        $plan->update($request->only(['nombre', 'precio', 'duracion_dias']));

        return response()->json([
            'mensaje' => 'Plan actualizado correctamente.',
            'datos' => new PlanResource($plan),
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $plan = Plan::where('coach_id', $coach->id)->findOrFail($id);

        $plan->delete();

        return response()->json(['mensaje' => 'Plan eliminado correctamente.']);
    }

    public function sincronizarStripe(Request $request, int $id): JsonResponse
    {
        // TODO: Implementar sincronización con Stripe
        return response()->json([
            'mensaje' => 'Funcionalidad de Stripe pendiente de implementar.',
        ], 501);
    }
}
