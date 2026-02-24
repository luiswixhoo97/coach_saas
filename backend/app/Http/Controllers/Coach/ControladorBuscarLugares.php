<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ControladorBuscarLugares extends Controller
{
    /**
     * Buscar lugares usando caché local primero, luego SerpAPI (Google Maps) - Solo México
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:3|max:255',
                'ciudad' => 'nullable|string|max:255', // Ciudad específica de México (opcional)
            ]);

            $query = $request->input('query');
            $ciudad = $request->input('ciudad'); // Ciudad opcional (ej: "Guadalajara, Guadalajara, Jalisco, Mexico")

            Log::info('Iniciando búsqueda de lugares', [
                'query' => $query,
                'ciudad' => $ciudad,
            ]);

            // PASO 1: Buscar primero en la base de datos local (caché global)
            $ubicacionesEnBD = Ubicacion::buscarPorQuery($query)->limit(10)->get();
            
            if ($ubicacionesEnBD->isNotEmpty()) {
                Log::info('Resultados encontrados en caché local', [
                    'count' => $ubicacionesEnBD->count(),
                    'query' => $query,
                ]);

                $lugares = $ubicacionesEnBD->map(function ($ubicacion) {
                    return [
                        'id' => $ubicacion->id,
                        'nombre' => $ubicacion->nombre,
                        'direccion' => $ubicacion->direccion,
                        'direccion_completa' => $ubicacion->direccion,
                        'link_google_maps' => $ubicacion->link_google_maps,
                        'telefono' => null,
                        'rating' => null,
                        'reviews' => null,
                        'tipo' => '',
                        'coordenadas' => ['lat' => null, 'lng' => null],
                    ];
                })->toArray();

                return response()->json([
                    'datos' => $lugares,
                    'total' => count($lugares),
                    'fuente' => 'cache', // Indicar que viene del caché
                ]);
            }

            // PASO 2: Si no hay resultados en BD, llamar a la API de SerpAPI
            Log::info('No se encontraron resultados en caché, consultando API de SerpAPI', [
                'query' => $query,
            ]);

            $apiKey = config('services.serpapi.key') ?: env('SERPAPI_KEY');
            if (!$apiKey || $apiKey === '') {
                Log::warning('SerpAPI key no configurada');
                return response()->json([
                    'mensaje' => 'API key de SerpAPI no configurada. Contacta al administrador.',
                ], 500);
            }

            // Endpoint de SerpAPI para Google
            $url = 'https://serpapi.com/search.json';
            
            $params = [
                'engine' => 'google',
                'q' => $query,
                'api_key' => $apiKey,
                'hl' => 'es', // Idioma español
                'gl' => 'mx',
                'location' => 'Mexico', // País: México (fijo)
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
                'has_knowledge_graph' => isset($data['knowledge_graph']),
                'has_organic_results' => isset($data['organic_results']),
            ]);
            
            // Procesar resultados de Google
            $lugares = [];
            
            // El engine "google" puede devolver resultados en "local_results" o "knowledge_graph"
            $localResults = $data['local_results'] ?? [];
            $knowledgeGraph = $data['knowledge_graph'] ?? null;
            
            // Si hay local_results, procesarlos
            if (is_array($localResults) && !empty($localResults)) {
                $lugares = collect($localResults)
                    ->take(10)
                    ->filter(function ($item) {
                        // Solo procesar arrays, ignorar strings u otros tipos
                        return is_array($item);
                    })
                    ->map(function ($item) {
                        $gpsCoords = $item['gps_coordinates'] ?? [];
                        
                        // Extraer link de Google Maps si está disponible
                        // Primero intentar desde gmap_id o place_id
                        $linkGoogleMaps = null;
                        if (isset($item['gmap_id'])) {
                            $linkGoogleMaps = "https://www.google.com.mx/maps/place/?q=place_id:" . $item['gmap_id'];
                        } elseif (isset($item['place_id'])) {
                            $linkGoogleMaps = "https://www.google.com.mx/maps/place/?q=place_id:" . $item['place_id'];
                        }
                        
                        return [
                            'id' => $item['data_id'] ?? $item['place_id'] ?? uniqid(),
                            'nombre' => $item['title'] ?? '',
                            'direccion' => $item['address'] ?? '',
                            'direccion_completa' => $this->construirDireccionCompleta($item),
                            'link_google_maps' => $linkGoogleMaps, // Link de Google Maps - este es el que se guardará
                            'telefono' => $item['phone'] ?? null,
                            'rating' => $item['rating'] ?? null,
                            'reviews' => $item['reviews'] ?? $item['review_count'] ?? null,
                            'tipo' => $item['type'] ?? '',
                            'coordenadas' => [
                                'lat' => $gpsCoords['latitude'] ?? null,
                                'lng' => $gpsCoords['longitude'] ?? null,
                            ],
                        ];
                    })
                    ->filter(function ($lugar) {
                        return !empty($lugar['nombre']) || !empty($lugar['direccion']);
                    })
                    ->toArray();
            }
            
            // Si hay local_map, intentar extraer el link desde ahí
            if (empty($lugares) && isset($data['local_map'])) {
                $localMap = $data['local_map'];
                // local_map puede ser un objeto con un link directo
                if (isset($localMap['link']) && strpos($localMap['link'], 'google.com.mx/maps') !== false) {
                    $lugares[] = [
                        'id' => uniqid(),
                        'nombre' => $localMap['title'] ?? 'Ubicación',
                        'direccion' => $localMap['address'] ?? '',
                        'direccion_completa' => $localMap['address'] ?? '',
                        'link_google_maps' => $localMap['link'],
                        'telefono' => null,
                        'rating' => null,
                        'reviews' => null,
                        'tipo' => '',
                        'coordenadas' => ['lat' => null, 'lng' => null],
                    ];
                }
            }
            
            // Si no hay local_results pero hay knowledge_graph, procesarlo
            if (empty($lugares) && $knowledgeGraph) {
                $kg = $knowledgeGraph;
                
                // Extraer link de Google Maps desde web_results dentro de knowledge_graph
                $linkGoogleMaps = null;
                $webResults = $kg['web_results'] ?? [];
                
                // Buscar el link de Google Maps en web_results (el primer link que contenga google.com.mx/maps)
                foreach ($webResults as $webResult) {
                    if (isset($webResult['link']) && strpos($webResult['link'], 'google.com.mx/maps') !== false) {
                        $linkGoogleMaps = $webResult['link'];
                        break;
                    }
                }
                
                // Si no se encontró en web_results, intentar construir desde place_id
                if (!$linkGoogleMaps && isset($kg['place_id'])) {
                    $linkGoogleMaps = "https://www.google.com.mx/maps/place/?q=place_id:" . $kg['place_id'];
                }
                
                // Construir dirección completa desde knowledge_graph
                $direccionCompleta = $kg['dirección'] ?? '';
                
                $lugar = [
                    'id' => $kg['place_id'] ?? $kg['kgmid'] ?? uniqid(),
                    'nombre' => $kg['title'] ?? '',
                    'direccion' => $kg['dirección'] ?? '',
                    'direccion_completa' => $direccionCompleta,
                    'link_google_maps' => $linkGoogleMaps, // Link de Google Maps - este es el que se guardará
                    'telefono' => $kg['teléfono'] ?? null,
                    'rating' => $kg['rating'] ?? null,
                    'reviews' => $kg['review_count'] ?? null,
                    'tipo' => $kg['type'] ?? '',
                    'coordenadas' => ['lat' => null, 'lng' => null],
                ];
                
                // Solo agregar si tiene nombre o dirección
                if (!empty($lugar['nombre']) || !empty($lugar['direccion'])) {
                    $lugares[] = $lugar;
                }
                
                Log::info('Lugar procesado desde knowledge_graph', [
                    'nombre' => $lugar['nombre'],
                    'direccion' => $lugar['direccion'],
                    'link_google_maps' => $linkGoogleMaps,
                ]);
            }
            
            Log::info('Lugares procesados desde API', ['count' => count($lugares)]);

            // PASO 3: Guardar los resultados en la base de datos (caché global)
            if (!empty($lugares)) {
                $this->guardarUbicacionesEnCache($lugares);
            }

            return response()->json([
                'datos' => $lugares,
                'total' => count($lugares),
                'fuente' => 'api', // Indicar que viene de la API
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

    /**
     * Guardar ubicaciones en el caché global (base de datos)
     * Usa updateOrCreate para evitar duplicados basándose en link_google_maps
     */
    private function guardarUbicacionesEnCache(array $lugares): void
    {
        foreach ($lugares as $lugar) {
            // Solo guardar si tiene link_google_maps (identificador único)
            if (!empty($lugar['link_google_maps']) && !empty($lugar['nombre'])) {
                try {
                    Ubicacion::updateOrCreate(
                        ['link_google_maps' => $lugar['link_google_maps']],
                        [
                            'nombre' => $lugar['nombre'],
                            'direccion' => $lugar['direccion'] ?? $lugar['direccion_completa'] ?? '',
                        ]
                    );
                } catch (\Exception $e) {
                    // Log del error pero continuar con los demás lugares
                    Log::warning('Error al guardar ubicación en caché', [
                        'error' => $e->getMessage(),
                        'link_google_maps' => $lugar['link_google_maps'],
                    ]);
                }
            }
        }

        Log::info('Ubicaciones guardadas en caché', [
            'total_procesadas' => count($lugares),
        ]);
    }
}

