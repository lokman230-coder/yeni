#!/usr/bin/env bash
set -euo pipefail
if [ ! -x "$ANDROID_HOME/cmdline-tools/latest/bin/sdkmanager" ]; then
  mkdir -p "$ANDROID_HOME/cmdline-tools"
  tmp=$(mktemp -d)
  curl -fsSL -o "$tmp/tools.zip" "https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip"
  unzip -q "$tmp/tools.zip" -d "$tmp"
  rm -rf "$ANDROID_HOME/cmdline-tools/latest"
  mv "$tmp/cmdline-tools" "$ANDROID_HOME/cmdline-tools/latest"
  rm -rf "$tmp"
fi
yes | sdkmanager --licenses >/dev/null || true
sdkmanager "platform-tools" "platforms;android-${ANDROID_API:-34}" "build-tools;${ANDROID_BUILD_TOOLS:-34.0.0}"
