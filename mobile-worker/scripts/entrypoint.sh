#!/usr/bin/env bash
set -euo pipefail
mkdir -p "$ANDROID_HOME" "$FLUTTER_HOME" /opt/ahost-worker/storage /opt/ahost-worker/builds /opt/ahost-worker/output
/opt/ahost-worker/scripts/install-android.sh
/opt/ahost-worker/scripts/install-flutter.sh
exec php -S 0.0.0.0:"${WORKER_PORT:-8090}" -t /opt/ahost-worker /opt/ahost-worker/worker.php
