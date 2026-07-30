#!/usr/bin/env bash
set -euo pipefail
if [ ! -x "$FLUTTER_HOME/bin/flutter" ]; then
  tmp=$(mktemp -d)
  version="${FLUTTER_VERSION:-3.24.5}"
  curl -fsSL -o "$tmp/flutter.tar.xz" "https://storage.googleapis.com/flutter_infra_release/releases/stable/linux/flutter_linux_${version}-stable.tar.xz"
  mkdir -p "$FLUTTER_HOME"
  tar -xJf "$tmp/flutter.tar.xz" --strip-components=1 -C "$FLUTTER_HOME"
  rm -rf "$tmp"
fi
flutter config --no-analytics
flutter precache --android
flutter doctor -v || true
