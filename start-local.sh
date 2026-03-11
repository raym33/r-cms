#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
RUNTIME_DIR="$ROOT/.linuxcms-runtime"
PID_FILE="$RUNTIME_DIR/server.pid"
LOG_FILE="$RUNTIME_DIR/server.log"
HOST="${LINUXCMS_HOST:-127.0.0.1}"
PORT="${LINUXCMS_PORT:-8088}"
URL="http://$HOST:$PORT/r-admin/"

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

if is_running; then
  echo "LinuxCMS already running at $URL"
  echo "PID: $(cat "$PID_FILE")"
else
  cd "$ROOT"
  nohup php -S "$HOST:$PORT" "$ROOT/router.php" >>"$LOG_FILE" 2>&1 &
  echo $! > "$PID_FILE"
  sleep 1
  if ! kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
    echo "LinuxCMS failed to start. Check log:"
    echo "  $LOG_FILE"
    exit 1
  fi
  echo "LinuxCMS started at $URL"
  echo "PID: $(cat "$PID_FILE")"
  echo "Log: $LOG_FILE"
fi

if [ "${1:-}" = "--open" ]; then
  if command -v open >/dev/null 2>&1; then
    open "$URL" || true
  elif command -v xdg-open >/dev/null 2>&1; then
    xdg-open "$URL" || true
  fi
fi
