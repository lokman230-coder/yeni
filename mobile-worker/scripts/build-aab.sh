#!/usr/bin/env bash
set -euo pipefail
PROJECT_DIR="${1:?project directory required}"
OUTPUT_DIR="${2:-/opt/ahost-worker/output}"
[ -f "$PROJECT_DIR/pubspec.yaml" ] || { echo "pubspec.yaml bulunamadı" >&2; exit 2; }
mkdir -p "$OUTPUT_DIR"
if [ "${APPLY_MOBILE_CONFIG:-1}" = "1" ]; then /opt/ahost-worker/scripts/apply-mobile-config.sh "$PROJECT_DIR"; fi
cd "$PROJECT_DIR"
flutter pub get
flutter build appbundle --release
cp -f build/app/outputs/bundle/release/app-release.aab "$OUTPUT_DIR/app-release.aab"
echo "$OUTPUT_DIR/app-release.aab"