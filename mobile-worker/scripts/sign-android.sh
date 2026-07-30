#!/usr/bin/env bash
set -euo pipefail
APK="${1:?apk path required}"
KEYSTORE="${KEYSTORE_PATH:-/opt/ahost-worker/storage/release.keystore}"
ALIAS="${KEY_ALIAS:?KEY_ALIAS required}"
STORE_PASS="${KEYSTORE_PASSWORD:?KEYSTORE_PASSWORD required}"
KEY_PASS="${KEY_PASSWORD:-$STORE_PASS}"
[ -f "$APK" ] || { echo "APK bulunamadı" >&2; exit 2; }
[ -f "$KEYSTORE" ] || { echo "Keystore bulunamadı: $KEYSTORE" >&2; exit 2; }
ZIPALIGN="$(find "$ANDROID_HOME/build-tools" -name zipalign | sort -V | tail -1)"
APKSIGNER="$(find "$ANDROID_HOME/build-tools" -name apksigner | sort -V | tail -1)"
[ -x "$ZIPALIGN" ] && [ -x "$APKSIGNER" ] || { echo "Android build tools bulunamadı" >&2; exit 2; }
SIGNED="${APK%.apk}-signed.apk"
ALIGNED="${APK%.apk}-aligned.apk"
"$ZIPALIGN" -f 4 "$APK" "$ALIGNED"
"$APKSIGNER" sign --ks "$KEYSTORE" --ks-key-alias "$ALIAS" --ks-pass "pass:$STORE_PASS" --key-pass "pass:$KEY_PASS" --out "$SIGNED" "$ALIGNED"
"$APKSIGNER" verify --verbose "$SIGNED"
rm -f "$ALIGNED"
echo "$SIGNED"