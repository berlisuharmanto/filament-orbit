#!/usr/bin/env bash
set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
DIST_DIR="$DIR/../bin/dist"
mkdir -p "$DIST_DIR"

VERSION="1.0.0"
BUILD_DATE=$(date -u +"%Y-%m-%d")

PLATFORMS=(
    "linux/amd64/dns-manager-linux-amd64"
    "linux/arm64/dns-manager-linux-arm64"
    "darwin/amd64/dns-manager-darwin-amd64"
    "darwin/arm64/dns-manager-darwin-arm64"
    "windows/amd64/dns-manager-windows-amd64.exe"
)

echo "==> Building dns-manager v$VERSION binaries for multiple architectures..."

for PLATFORM in "${PLATFORMS[@]}"; do
    IFS="/" read -r -a PARTS <<< "$PLATFORM"
    GOOS="${PARTS[0]}"
    GOARCH="${PARTS[1]}"
    OUTPUT_NAME="${PARTS[2]}"
    
    echo "  -> Compiling for $GOOS/$GOARCH -> $OUTPUT_NAME..."
    CGO_ENABLED=0 GOOS=$GOOS GOARCH=$GOARCH go build \
        -ldflags="-s -w -X 'main.Version=$VERSION' -X 'main.BuildDate=$BUILD_DATE'" \
        -o "$DIST_DIR/$OUTPUT_NAME" \
        ./cmd/dns-manager
done

# Also compile default host binary to bin/dns-manager
mkdir -p "$DIR/../bin"
CGO_ENABLED=0 go build \
    -ldflags="-s -w -X 'main.Version=$VERSION' -X 'main.BuildDate=$BUILD_DATE'" \
    -o "$DIR/../bin/dns-manager" \
    ./cmd/dns-manager

echo "==> Build complete! Output directory: $DIST_DIR"
ls -la "$DIST_DIR"
