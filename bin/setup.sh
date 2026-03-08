#!/usr/bin/env bash

set -euo pipefail

echo "Setting up WooCommerce DHL Shipping plugin..."

if ! command -v composer >/dev/null 2>&1; then
	echo "Error: Composer is not installed. Please install Composer first."
	echo "Visit https://getcomposer.org/download/ for installation instructions."
	exit 1
fi

if ! command -v npm >/dev/null 2>&1; then
	echo "Error: npm is not installed. Please install Node.js/npm first."
	echo "Visit https://nodejs.org/ for installation instructions."
	exit 1
fi

echo "Installing Composer dependencies..."
composer install

echo "Installing npm dependencies..."
npm ci

echo "Setup complete."
echo "Next steps:"
echo "  - Run tests with: composer run-script test"
echo "  - Run linting with: composer run-script phpcs"
echo "  - Build a release zip with: npm run build"
