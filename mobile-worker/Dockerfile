FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates curl wget unzip git zip jq php-cli php-curl php-json openjdk-17-jdk nodejs npm bash python3 && rm -rf /var/lib/apt/lists/*
WORKDIR /opt/ahost-worker
COPY scripts/ /opt/ahost-worker/scripts/
COPY worker.php /opt/ahost-worker/worker.php
RUN chmod +x /opt/ahost-worker/scripts/*.sh
ENV ANDROID_HOME=/opt/android-sdk
ENV ANDROID_SDK_ROOT=/opt/android-sdk
ENV FLUTTER_HOME=/opt/flutter
ENV PATH=/opt/flutter/bin:/opt/flutter/bin/cache/dart-sdk/bin:/opt/android-sdk/cmdline-tools/latest/bin:/opt/android-sdk/platform-tools:/opt/android-sdk/build-tools/34.0.0:$PATH
EXPOSE 8090
ENTRYPOINT ["/opt/ahost-worker/scripts/entrypoint.sh"]
