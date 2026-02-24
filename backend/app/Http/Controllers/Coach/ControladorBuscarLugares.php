<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ControladorBuscarLugares extends Controller
{
    /**
     * Buscar lugares usando SerpAPI (Google Maps) - Solo México
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:3|max:255',
                'ciudad' => 'nullable|string|max:255', // Ciudad específica de México (opcional)
            ]);

            $apiKey = config('services.serpapi.key') ?: env('SERPAPI_KEY');
            if (!$apiKey || $apiKey === '') {
                Log::warning('SerpAPI key no configurada');
                return response()->json([
                    'mensaje' => 'API key de SerpAPI no configurada. Contacta al administrador.',
                ], 500);
            }

            Log::info('Iniciando búsqueda de lugares', [
                'query' => $request->input('query'),
                'ciudad' => $request->input('ciudad'),
            ]);
            $query = $request->input('query');
            $ciudad = $request->input('ciudad'); // Ciudad opcional (ej: "Guadalajara, Guadalajara, Jalisco, Mexico")

            // Endpoint de SerpAPI para Google
            $url = 'https://serpapi.com/search.json';
            
            $params = [
                'engine' => 'google',
                'q' => 'Harder+Fitness+Center',//$query,
                'api_key' => '119b5e05ba8f17802517d83f4ca77b06d3ba0e7539d4469c8a6899f75b3dde88',
                'hl' => 'es', // Idioma español
                'gl' => 'mx',
                'location' =>  'Guadalajara,+Guadalajara,+Jalisco,+Mexico', // País: México (fijo)
                'google_domain' => 'google.com.mx' // Dominio de Google México
            ];
            
            // Si se especifica ciudad, agregar location con formato completo
            if ($ciudad) {
                // Formato: "Ciudad, Estado, Mexico" o "Ciudad, Ciudad, Estado, Mexico"
                // Si no incluye "Mexico" al final, agregarlo
                $location = trim($ciudad);
                if (stripos($location, 'mexico') === false) {
                    $location .= ', Mexico';
                }
                $params['location'] = $location;
            }

            /** @var \Illuminate\Http\Client\Response $response */
            $httpClient = Http::timeout(100);
            
            // En desarrollo, deshabilitar verificación SSL para evitar errores de certificado
            if (app()->environment('local', 'development')) {
                $httpClient = $httpClient->withoutVerifying();
            }
            
            $response = $httpClient->get($url, $params);

            Log::info('Respuesta de SerpAPI', [
                'response' => $response->body(),
            ]);

            $statusCode = $response->status();
            if ($statusCode !== 200) {
                $responseBody = $response->body();
                $errorData = $response->json();
                
                Log::error('Error en SerpAPI', [
                    'status' => $statusCode,
                    'body' => $responseBody,
                    'query' => $query,
                    'location' => $params['location'] ?? null,
                ]);

                $mensajeError = 'Error al buscar lugares.';
                if ($statusCode === 401) {
                    $mensajeError = 'API key de SerpAPI inválida o no configurada.';
                } elseif ($statusCode === 429) {
                    $mensajeError = 'Límite de solicitudes excedido. Intenta más tarde.';
                } elseif (isset($errorData['error'])) {
                    $mensajeError = $errorData['error'];
                }

                return response()->json([
                    'mensaje' => $mensajeError,
                    'error' => config('app.debug') ? [
                        'status' => $statusCode,
                        'body' => $errorData,
                    ] : null,
                ], $statusCode === 401 ? 401 : 500);
            }

            $data = $response->json();
            
            Log::info('Respuesta de SerpAPI recibida', [
                'keys' => array_keys($data ?? []),
                'has_local_results' => isset($data['local_results']),
                'has_organic_results' => isset($data['organic_results']),
                'local_results_count' => isset($data['local_results']) ? count($data['local_results']) : 0,
            ]);
            
            // Procesar resultados de Google (engine "google" devuelve local_results)
            $lugares = [];
            
            // El engine "google" devuelve resultados en "local_results"
            $localResults = $data['local_results'] ?? [];
            
            if (is_array($localResults) && !empty($localResults)) {
                $lugares = collect($localResults)
                    ->take(10)
                    ->map(function ($item) {
                        // Manejar estructura de datos de SerpAPI con engine "google"
                        $gpsCoords = $item['gps_coordinates'] ?? [];
                        
                        return [
                            'id' => $item['data_id'] ?? $item['place_id'] ?? uniqid(),
                            'nombre' => $item['title'] ?? '',
                            'direccion' => $item['address'] ?? '',
                            'direccion_completa' => $this->construirDireccionCompleta($item),
                            'telefono' => $item['phone'] ?? null,
                            'rating' => $item['rating'] ?? null,
                            'reviews' => $item['reviews'] ?? null,
                            'tipo' => $item['type'] ?? '',
                            'coordenadas' => [
                                'lat' => $gpsCoords['latitude'] ?? null,
                                'lng' => $gpsCoords['longitude'] ?? null,
                            ],
                        ];
                    })
                    ->filter(function ($lugar) {
                        // Filtrar lugares sin nombre o dirección
                        return !empty($lugar['nombre']) || !empty($lugar['direccion']);
                    })
                    ->toArray();
                    
                Log::info('Lugares procesados', ['count' => count($lugares)]);
            } else {
                Log::warning('No se encontraron resultados en local_results', [
                    'data_keys' => array_keys($data ?? []),
                    'sample_data' => config('app.debug') ? json_encode(array_slice($data ?? [], 0, 2, true)) : null,
                ]);
            }

            return response()->json([
                'datos' => $lugares,
                'total' => count($lugares),
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Error de conexión con SerpAPI', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'mensaje' => 'Error de conexión con el servicio de búsqueda. Verifica tu conexión a internet.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Excepción al buscar lugares con SerpAPI', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'query' => $query ?? null,
            ]);

            return response()->json([
                'mensaje' => 'Error al buscar lugares.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Construir dirección completa desde los datos del lugar
     */
    private function construirDireccionCompleta(array $item): string
    {
        $partes = [];
        
        if (!empty($item['address'])) {
            $partes[] = $item['address'];
        }
        
        if (!empty($item['address_line_2'])) {
            $partes[] = $item['address_line_2'];
        }

        // Asegurar que incluya México si no está presente
        $direccion = implode(', ', $partes) ?: ($item['title'] ?? '');
        if (!empty($direccion) && stripos($direccion, 'méxico') === false && stripos($direccion, 'mexico') === false) {
            $direccion .= ', México';
        }

        return $direccion;
    }
}

