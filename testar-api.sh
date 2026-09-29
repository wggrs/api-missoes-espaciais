#!/usr/bin/env bash
set -euo pipefail

base="${1:-http://localhost:8080}"
tmp="$(mktemp)"
trap 'rm -f "$tmp"' EXIT

check() {
  local metodo="$1" rota="$2" esperado="$3" corpo="${4:-}"
  local codigo
  if [[ -n "$corpo" ]]; then
    codigo="$(curl -sS -o "$tmp" -w '%{http_code}' -X "$metodo" -H 'Content-Type: application/json' -d "$corpo" "$base$rota")"
  else
    codigo="$(curl -sS -o "$tmp" -w '%{http_code}' -X "$metodo" "$base$rota")"
  fi
  [[ "$codigo" == "$esperado" ]] || { echo "Falhou: $metodo $rota → $codigo (esperado $esperado)"; cat "$tmp"; exit 1; }
  echo "OK: $metodo $rota → $codigo"
}

check GET /status 200
check GET /missoes 200
check GET /missoes/1 200
check GET /missoes/999999 404
check POST /missoes 201 '{"nome":"Teste da API","ano":2027,"agencia":"NASA","status":"Planejada"}'
id="$(python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))["id"])' "$tmp")"
check GET "/missoes/$id" 200
check PUT "/missoes/$id" 200 '{"nome":"Teste da API","ano":2027,"agencia":"NASA","status":"Em preparação"}'
check DELETE "/missoes/$id" 204
check GET "/missoes/$id" 404
echo 'Todas as rotas responderam com os códigos esperados.'
