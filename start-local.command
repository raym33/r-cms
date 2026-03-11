#!/bin/bash
set -euo pipefail
cd "$(dirname "$0")"
./start-local.sh --open
echo
echo "LinuxCMS is running."
echo "You can close this window after the browser opens."
read -r -p "Press Enter to close..."
