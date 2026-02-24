import { ref } from 'vue'
import { useApi } from './useApi'

export function usePlacesAutocomplete() {
  const sugerencias = ref([])
  const cargando = ref(false)
  const error = ref(null)
  const { get } = useApi()

  /**
   * Buscar lugares usando SerpAPI (a través del backend) - Solo México
   * @param {string} query - Texto de búsqueda
   * @param {string} ciudad - Ciudad opcional de México (ej: "Ciudad de México", "Guadalajara")
   */
  async function buscarLugares(query, ciudad = null) {
    if (!query || query.trim().length < 3) {
      sugerencias.value = []
      return
    }

    const queryTrimmed = query.trim()
    if (queryTrimmed.length < 3) {
      sugerencias.value = []
      return
    }

    cargando.value = true
    error.value = null

    try {
      console.log('Buscando lugares en México:', queryTrimmed, ciudad ? `en ${ciudad}` : '')
      
      const params = new URLSearchParams({ query: queryTrimmed })
      if (ciudad) {
        params.append('ciudad', ciudad)
      }

      const response = await get(`/coach/buscar-lugares?${params.toString()}`)
      
      console.log('Resultados encontrados en México:', response.datos?.length || 0)
      
      if (response.datos && Array.isArray(response.datos)) {
        sugerencias.value = response.datos.map(item => ({
          id: item.id,
          nombre: item.nombre,
          direccion: item.direccion_completa || item.direccion,
          direccion_corta: item.direccion,
          link_google_maps: item.link_google_maps, // Link de Google Maps que se guardará
          telefono: item.telefono,
          rating: item.rating,
          reviews: item.reviews,
          tipo: item.tipo,
          coordenadas: item.coordenadas,
        }))
      } else {
        sugerencias.value = []
      }
      
      console.log('Sugerencias procesadas:', sugerencias.value.length)
    } catch (err) {
      console.error('Error buscando lugares:', err)
      error.value = err.response?.data?.mensaje || err.message || 'Error al buscar lugares. Intenta de nuevo.'
      sugerencias.value = []
    } finally {
      cargando.value = false
    }
  }

  /**
   * Limpiar sugerencias
   */
  function limpiarSugerencias() {
    sugerencias.value = []
    error.value = null
  }

  return {
    sugerencias,
    cargando,
    error,
    buscarLugares,
    limpiarSugerencias
  }
}

