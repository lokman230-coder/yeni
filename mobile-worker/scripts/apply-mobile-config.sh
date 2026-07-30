#!/usr/bin/env bash
set -euo pipefail
PROJECT_DIR="${1:?flutter project directory required}"
APP_NAME="${APP_NAME:-Ahost Mobile App}"
PACKAGE_NAME="${PACKAGE_NAME:-com.ahost.mobileapp}"
FIREBASE_JSON="${FIREBASE_JSON_PATH:-}"
[ -f "$PROJECT_DIR/pubspec.yaml" ] || { echo "Flutter projesi bulunamadı" >&2; exit 2; }
mkdir -p "$PROJECT_DIR/android/app/src/main"
if [ -n "$FIREBASE_JSON" ] && [ -f "$FIREBASE_JSON" ]; then cp "$FIREBASE_JSON" "$PROJECT_DIR/android/app/google-services.json"; fi
MANIFEST="$PROJECT_DIR/android/app/src/main/AndroidManifest.xml"
if [ -f "$MANIFEST" ]; then sed -i 's/android:label="[^"]*"/android:label="'"$APP_NAME"'"/' "$MANIFEST" || true; fi
GRADLE="$PROJECT_DIR/android/app/build.gradle"
if [ -f "$GRADLE" ]; then sed -i 's/applicationId "[^"]*"/applicationId "'"$PACKAGE_NAME"'"/' "$GRADLE" || true; fi
printf 'mobile config applied: %s (%s)\n' "$APP_NAME" "$PACKAGE_NAME"
