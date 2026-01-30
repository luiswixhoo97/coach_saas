#!/usr/bin/env bash
# Verifica endpoints y relaciones. Ejecutar con: ./tests/verificar_endpoints.sh
# Requiere: php artisan serve en ejecución (http://127.0.0.1:8000)

BASE="http://127.0.0.1:8000/api/v1"
OK=0
FAIL=0

result() {
  if [ "$1" -eq 0 ]; then
    echo "  OK   $2"
    OK=$((OK + 1))
  else
    echo "  FAIL $2"
    FAIL=$((FAIL + 1))
  fi
}

echo "=== Login ==="
ADMIN_RESP=$(curl -s -X POST "$BASE/auth/login" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"admin@test.com","password":"password"}')
ADMIN_TOKEN=$(echo "$ADMIN_RESP" | grep -o '"token":"[^"]*"' | sed 's/"token":"//;s/"$//')
[ -n "$ADMIN_TOKEN" ] && echo "Admin token obtenido" || echo "ERROR: No se pudo login admin"

COACH_RESP=$(curl -s -X POST "$BASE/auth/login" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"coach@test.com","password":"password"}')
COACH_TOKEN=$(echo "$COACH_RESP" | grep -o '"token":"[^"]*"' | sed 's/"token":"//;s/"$//')
[ -n "$COACH_TOKEN" ] && echo "Coach token obtenido" || echo "ERROR: No se pudo login coach"

CLIENTE_RESP=$(curl -s -X POST "$BASE/auth/login" -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"cliente@test.com","password":"password"}')
CLIENTE_TOKEN=$(echo "$CLIENTE_RESP" | grep -o '"token":"[^"]*"' | sed 's/"token":"//;s/"$//')
[ -n "$CLIENTE_TOKEN" ] && echo "Cliente token obtenido" || echo "ERROR: No se pudo login cliente"

echo ""
echo "=== Admin (relaciones: coaches, clientes, dashboard, pagos) ==="
curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/dashboard" | grep -q 200
result $? "GET /admin/dashboard"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/coaches" | grep -q 200
result $? "GET /admin/coaches"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/coaches/1" | grep -q 200
result $? "GET /admin/coaches/1 (coach con relaciones)"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/clientes" | grep -q 200
result $? "GET /admin/clientes"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/clientes/1" | grep -q 200
result $? "GET /admin/clientes/1 (cliente con coach y suscripciones)"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/pagos" | grep -q 200
result $? "GET /admin/pagos (pago -> suscripcion -> cliente, plan -> coach)"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $ADMIN_TOKEN" "$BASE/admin/reportes/ingresos" | grep -q 200
result $? "GET /admin/reportes/ingresos"

echo ""
echo "=== Coach (relaciones: clientes, planes, suscripciones, rutinas, ejercicios, etc.) ==="
curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/dashboard" | grep -q 200
result $? "GET /coach/dashboard"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/perfil" | grep -q 200
result $? "GET /coach/perfil"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/clientes" | grep -q 200
result $? "GET /coach/clientes"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/clientes/1" | grep -q 200
result $? "GET /coach/clientes/1"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/clientes/1/historial" | grep -q 200
result $? "GET /coach/clientes/1/historial (suscripciones, rutinas asignadas)"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/clientes/1/progreso" | grep -q 200
result $? "GET /coach/clientes/1/progreso (evaluaciones, parametros)"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/planes" | grep -q 200
result $? "GET /coach/planes"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/planes/1" | grep -q 200
result $? "GET /coach/planes/1"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/suscripciones" | grep -q 200
result $? "GET /coach/suscripciones"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/suscripciones/1" | grep -q 200
result $? "GET /coach/suscripciones/1"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/ejercicios" | grep -q 200
result $? "GET /coach/ejercicios"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/rutinas" | grep -q 200
result $? "GET /coach/rutinas"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/rutinas/1" | grep -q 200
result $? "GET /coach/rutinas/1 (ejercicios pivot)"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/parametros" | grep -q 200
result $? "GET /coach/parametros"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/evaluaciones" | grep -q 200
result $? "GET /coach/evaluaciones"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $COACH_TOKEN" "$BASE/coach/formularios" | grep -q 200
result $? "GET /coach/formularios"

echo ""
echo "=== Cliente (relaciones: perfil, rutinas asignadas, evaluaciones, progreso) ==="
curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/perfil" | grep -q 200
result $? "GET /cliente/perfil"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/rutinas" | grep -q 200
result $? "GET /cliente/rutinas"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/rutinas/1" | grep -q 200
result $? "GET /cliente/rutinas/1"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/evaluaciones" | grep -q 200
result $? "GET /cliente/evaluaciones"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/progreso" | grep -q 200
result $? "GET /cliente/progreso"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/formularios" | grep -q 200
result $? "GET /cliente/formularios"

curl -s -o /dev/null -w "%{http_code}" -H "Authorization: Bearer $CLIENTE_TOKEN" "$BASE/cliente/suscripcion" | grep -q 200
result $? "GET /cliente/suscripcion"

echo ""
echo "=== Resumen: $OK OK, $FAIL FAIL ==="
[ "$FAIL" -eq 0 ] && exit 0 || exit 1
