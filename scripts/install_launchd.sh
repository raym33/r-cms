#!/bin/bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RUNTIME_DIR="$ROOT/.linuxcms-runtime"
PLIST_DIR="$HOME/Library/LaunchAgents"
PLIST_PATH="$PLIST_DIR/com.raym33.linuxcms.plist"
LOG_FILE="$RUNTIME_DIR/server.log"
PID_FILE="$RUNTIME_DIR/server.pid"
HOST="${LINUXCMS_HOST:-127.0.0.1}"
PORT="${LINUXCMS_PORT:-8088}"

mkdir -p "$RUNTIME_DIR" "$PLIST_DIR"

cat > "$PLIST_PATH" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>com.raym33.linuxcms</string>
  <key>ProgramArguments</key>
  <array>
    <string>/bin/bash</string>
    <string>-lc</string>
    <string>cd "$ROOT" && echo \$\$ > "$PID_FILE" && exec php -S $HOST:$PORT "$ROOT/router.php"</string>
  </array>
  <key>WorkingDirectory</key>
  <string>$ROOT</string>
  <key>RunAtLoad</key>
  <true/>
  <key>KeepAlive</key>
  <true/>
  <key>StandardOutPath</key>
  <string>$LOG_FILE</string>
  <key>StandardErrorPath</key>
  <string>$LOG_FILE</string>
  <key>EnvironmentVariables</key>
  <dict>
    <key>PATH</key>
    <string>/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin</string>
  </dict>
</dict>
</plist>
PLIST

launchctl unload "$PLIST_PATH" >/dev/null 2>&1 || true
launchctl load "$PLIST_PATH"

echo "LinuxCMS launchd service installed."
echo "Admin URL: http://$HOST:$PORT/r-admin/"
echo "Plist: $PLIST_PATH"
echo "Log: $LOG_FILE"
