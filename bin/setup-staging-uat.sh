#!/usr/bin/env bash

set -euo pipefail

if ! command -v wp >/dev/null 2>&1; then
	echo "Error: wp-cli is required."
	exit 1
fi

INSTANCE_ID="${1:-}"

if [ -z "$INSTANCE_ID" ]; then
	echo "Usage: $0 <dhl-instance-id>"
	exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

wp eval-file "${SCRIPT_DIR}/setup-staging-uat.php" -- "$INSTANCE_ID"
