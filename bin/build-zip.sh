#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_NAME="${1:-woocommerce-shipping-dhl}"
ZIP_PATH="${ROOT_DIR}/${PLUGIN_NAME}.zip"
BUILD_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/${PLUGIN_NAME}.XXXXXX")"
DIST_DIR="${BUILD_ROOT}/${PLUGIN_NAME}"

cleanup() {
	rm -rf "${BUILD_ROOT}"
}
trap cleanup EXIT

rm -f "${ZIP_PATH}"
mkdir -p "${DIST_DIR}"

copy_path() {
	local source_path="$1"

	if [ -e "${ROOT_DIR}/${source_path}" ]; then
		rsync -a "${ROOT_DIR}/${source_path}" "${DIST_DIR}/"
	fi
}

copy_path "assets"
copy_path "includes"
copy_path "languages"
copy_path "README.md"
copy_path "changelog.txt"
copy_path "woocommerce-shipping-dhl.php"

if [ -f "${ROOT_DIR}/vendor/autoload_packages.php" ]; then
	mkdir -p "${DIST_DIR}/vendor"
	rsync -a "${ROOT_DIR}/vendor/autoload_packages.php" "${DIST_DIR}/vendor/"
	rsync -a "${ROOT_DIR}/vendor/composer" "${DIST_DIR}/vendor/"
	rsync -a "${ROOT_DIR}/vendor/jetpack-autoloader" "${DIST_DIR}/vendor/"
fi

find "${DIST_DIR}" -name '.DS_Store' -delete

(
	cd "${BUILD_ROOT}"
	zip -rq "${ZIP_PATH}" "${PLUGIN_NAME}"
)

printf 'Created %s\n' "${ZIP_PATH}"
