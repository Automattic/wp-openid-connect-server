#!/usr/bin/env bash

set -e
shopt -s extglob

function error {
  RED='\033[0;31m'
  NONE='\033[0m'
  printf "$RED$1$NONE\n"
  exit 1
}

if [ -z "$1" ]; then
    error "Provide a version, current version is $(jq '.version' composer.json)"
fi

VERSION=$1
if [[ $VERSION == v* ]]; then
  # Strip leading v.
  VERSION="${VERSION:1}"
fi

RELEASE_ROOT_DIR="$(pwd)/release"
RELEASE_DIR="$RELEASE_ROOT_DIR/$VERSION"
rm -rf "$RELEASE_DIR" && mkdir -p "$RELEASE_DIR"

PATHS_TO_INCLUDE=(
"src"
"openid-connect-server.php"
"uninstall.php"
"LICENSE"
"README.md"
)
for path in "${PATHS_TO_INCLUDE[@]}";do
  cp -r "$path" "$RELEASE_DIR/$path"
done

rm -rf "$RELEASE_DIR/build/.tmp"

COMPOSER_VENDOR_DIR=vendor_release composer install --no-ansi --no-dev --no-interaction --no-plugins --no-scripts --optimize-autoloader
mv vendor_release "$RELEASE_DIR/vendor"

# Rename release directory from version name (e.g. 1.2.3) to `openid-connect-server` so that root directory in the artifacts is named `openid-connect-server`.
# Then create the archives, and rename back to the versioned name (e.g. 1.2.3).
rm -rf "$RELEASE_ROOT_DIR/openid-connect-server"
mv "$RELEASE_DIR" "$RELEASE_ROOT_DIR/openid-connect-server"
cd "$RELEASE_ROOT_DIR"
zip -r "openid-connect-server-$VERSION.zip" openid-connect-server
tar -cvzf "openid-connect-server-$VERSION.tar.gz" openid-connect-server
mv "$RELEASE_ROOT_DIR/openid-connect-server" "$RELEASE_DIR"
