#!/bin/bash

# WooCommerce DHL Shipping Setup Script
# This script sets up the development environment for the WooCommerce DHL Shipping plugin.

echo "Setting up WooCommerce DHL Shipping plugin..."

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "Error: Composer is not installed. Please install Composer first."
    echo "Visit https://getcomposer.org/download/ for installation instructions."
    exit 1
fi

# Install Composer dependencies
echo "Installing Composer dependencies..."
composer install

# Create necessary directories if they don't exist
echo "Creating necessary directories..."
mkdir -p dist/css
mkdir -p dist/js
mkdir -p languages

# Check if dist/css/dhl-admin.css doesn't exist, copy from assets
if [ ! -f dist/css/dhl-admin.css ]; then
    echo "Copying CSS files to dist directory..."
    cp assets/css/dhl-admin.css dist/css/
fi

# Check if dist/js files don't exist, copy from assets
if [ ! -f dist/js/dhl-admin.js ]; then
    echo "Copying JS files to dist directory..."
    cp assets/js/dhl-admin.js dist/js/
    cp assets/js/checkout.js dist/js/
fi

echo "Setup complete!"
echo "To use the plugin, configure your WooCommerce shipping settings and enter your DHL API credentials."