#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
PID_FILE="$ROOT/.linuxcms-runtime/server.pid"

if [ ! -f "$PID_FILE" ]; then
  echo "LinuxCMS is not running."
  exit 0
fi

PID="$(cat "$PID_FILE" 2>/dev/null || true)"
if [ -z "${PID:-}" ]; then
  rm -f "$PID_FILE"
  echo "LinuxCMS is not running."
  exit 0
fi

if kill -0 "$PID" 2>/dev/null; then
  kill "$PID"
  echo "LinuxCMS stopped (PID $PID)."
else
  echo "LinuxCMS was not running."
fi

rm -f "$PID_FILE"
