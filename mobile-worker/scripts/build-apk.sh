#!/usr/bin/env bash
set -euo pipefail
PROJECT_DIR="${1:?project directory required}"
OUTPUT_DIR="${2:-/opt/ahost-worker/output}"
[ -f "$PROJECT_DIR/pubspec.yaml" ] || { echo "pubspec.yaml bulunamadı" >&2; exit 2; }
mkdir -p "$OUTPUT_DIR"
if [ "${APPLY_MOBILE_CONFIG:-1}" = "1" ]; then /opt/ahost-worker/scripts/apply-mobile-config.sh "$PROJECT_DIR"; fi
cd "$PROJECT_DIR"
flutter pub get
flutter build apk --release --split-per-abi
cp -f build/app/outputs/flutter-apk/app-release.apk "$OUTPUT_DIR/app-release.apk"
find build/app/outputs/flutter-apk -name 'app-*-release.apk' -exec cp -f {} "$OUTPUT_DIR/" \; || true
echo "$OUTPUT_DIR/app-release.apk"