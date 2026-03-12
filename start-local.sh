#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
RUNTIME_DIR="$ROOT/.linuxcms-runtime"
PID_FILE="$RUNTIME_DIR/server.pid"
LOG_FILE="$RUNTIME_DIR/server.log"
HOST="${LINUXCMS_HOST:-127.0.0.1}"
PORT="${LINUXCMS_PORT:-8088}"
URL="http://$HOST:$PORT/r-admin/"
HEALTH_URL="http://$HOST:$PORT/api/health"

mkdir -p "$RUNTIME_DIR"

is_running() {
  if [ ! -f "$PID_FILE" ]; then
    return 1
  fi
  PID="$(cat "$PID_FILE" 2>/dev/null || true)"
  if [ -z "${PID:-}" ]; then
    return 1
  fi
  if kill -0 "$PID" 2>/dev/null; then
    return 0
  fi
  rm -f "$PID_FILE"
  return 1
}

health_ok() {
  if command -v curl >/dev/null 2>&1; then
    curl -fsS --max-time 2 "$HEALTH_URL" >/dev/null 2>&1
    return $?
  fi
  php -r '$url=$argv[1]; $context=stream_context_create(["http"=>["timeout"=>2]]); $body=@file_get_contents($url,false,$context); exit($body===false?1:0);' "$HEALTH_URL" >/dev/null 2>&1
}

wait_for_health() {
  local attempts=30
  local pid
  pid="$(cat "$PID_FILE" 2>/dev/null || true)"
  while [ "$attempts" -gt 0 ]; do
    if health_ok; then
      return 0
    fi
    if [ -n "${pid:-}" ] && ! kill -0 "$pid" 2>/dev/null; then
      return 1
    fi
    sleep 0.5
    attempts=$((attempts - 1))
  done
  return 1
}

if is_running; then
  echo "LinuxCMS already running at $URL"
  echo "PID: $(cat "$PID_FILE")"
  if health_ok; then
    echo "Health check: OK"
  else
    echo "Health check: FAILED"
    echo "Server process exists but $HEALTH_URL is not responding."
  fi
else
  cd "$ROOT"
  nohup php -S "$HOST:$PORT" "$ROOT/router.php" >>"$LOG_FILE" 2>&1 &
  echo $! > "$PID_FILE"
  if ! kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
    echo "LinuxCMS failed to start. Check log:"
    echo "  $LOG_FILE"
    exit 1
  fi
  if ! wait_for_health; then
    echo "LinuxCMS started but health check failed: $HEALTH_URL"
    echo "Last log lines:"
    tail -n 30 "$LOG_FILE" 2>/dev/null || true
    exit 1
  fi
  echo "LinuxCMS started at $URL"
  echo "PID: $(cat "$PID_FILE")"
  echo "Log: $LOG_FILE"
  echo "Health: $HEALTH_URL"
fi

if [ "${1:-}" = "--open" ]; then
  if command -v open >/dev/null 2>&1; then
    open "$URL" || true
  elif command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$URL" || true
  fi
fi
