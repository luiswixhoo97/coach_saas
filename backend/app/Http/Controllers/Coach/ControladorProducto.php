<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\ActualizarProductoRequest;
use App\Http\Requests\Coach\AlmacenarProductoRequest;
use App\Http\Resources\PaginacionCollection;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ControladorProducto extends Controller
{
    private function getCoach(Request $request)
    {
        return $request->user()->coach;
    }

    public function index(Request $request): PaginacionCollection
    {
        $coach = $this->getCoach($request);

        $query = Producto::where('coach_id', $coach->id);

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('stock_bajo')) {
            $query->stockBajo();
        }

        $productos = $query->orderBy('nombre')->paginate(15);

        return new PaginacionCollection($productos, ProductoResource::class);
    }

    public function almacenar(AlmacenarProductoRequest $request): JsonResponse
    {
        $coach = $this->getCoach($request);

        $producto = Producto::create([
            'coach_id' => $coach->id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'stock' => $request->stock,
            'stock_minimo' => $request->stock_minimo ?? 0,
            'tipo' => $request->tipo,
        ]);

        return response()->json([
            'mensaje' => 'Producto creado correctamente.',
            'datos' => new ProductoResource($producto),
        ], 201);
    }

    public function mostrar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $producto = Producto::where('coach_id', $coach->id)->findOrFail($id);

        return response()->json(['datos' => new ProductoResource($producto)]);
    }

    public function actualizar(ActualizarProductoRequest $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $producto = Producto::where('coach_id', $coach->id)->findOrFail($id);

        $producto->update($request->only(['nombre', 'descripcion', 'precio', 'stock_minimo', 'tipo']));

        return response()->json([
            'mensaje' => 'Producto actualizado correctamente.',
            'datos' => new ProductoResource($producto),
        ]);
    }

    public function eliminar(Request $request, int $id): JsonResponse
    {
        $coach = $this->getCoach($request);

        $producto = Producto::where('coach_id', $coach->id)->findOrFail($id);

        $producto->delete();

        return response()->json(['mensaje' => 'Producto eliminado correctamente.']);
    }

    public function ajustarStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'cantidad' => 'required|integer',
            'operacion' => 'required|in:agregar,reducir,establecer',
        ]);

        $coach = $this->getCoach($request);

        $producto = Producto::where('coach_id', $coach->id)->findOrFail($id);

        switch ($request->operacion) {
            case 'agregar':
                $producto->aumentarStock($request->cantidad);
                break;
            case 'reducir':
                if (!$producto->reducirStock($request->cantidad)) {
                    return response()->json([
                        'mensaje' => 'Stock insuficiente.',
                    ], 400);
                }
                break;
            case 'establecer':
                $producto->update(['stock' => max(0, $request->cantidad)]);
                break;
        }

        return response()->json([
            'mensaje' => 'Stock actualizado correctamente.',
            'datos' => ['stock' => $producto->fresh()->stock],
        ]);
    }
}
