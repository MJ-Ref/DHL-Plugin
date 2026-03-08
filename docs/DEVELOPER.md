# WooCommerce DHL Shipping - Developer Documentation

This document is the current technical reference for the plugin implementation in this repository.

## Architecture

The plugin is organized around seven runtime areas:

1. Bootstrap
   - `woocommerce-shipping-dhl.php`
   - declares plugin constants, activation/deactivation hooks, and bootstraps the plugin after WooCommerce loads
2. Install and migrations
   - `includes/class-install.php`
   - persists schema version, runs settings normalization migrations, and surfaces upgrade notices
3. Shipping method
   - `includes/class-wc-shipping-dhl.php`
   - defines settings, configuration preflight, packing logic, service filtering, rate requests, and checkout rate calculation
4. Admin and order operations
   - `includes/class-wc-shipping-dhl-admin.php`
   - `includes/class-order-operations.php`
   - powers settings UI, plugin action links, admin notices, order actions, tracking sync, and private document downloads
5. API layer
   - `includes/api/rest/class-api-client.php`
   - `includes/api/rest/class-address-validator.php`
   - `includes/api/rest/class-shipment-client.php`
   - `includes/api/rest/class-oauth.php`
6. Product data editing
   - `includes/class-product-editor.php`
   - exposes DHL commodity-code fields in the WooCommerce product editor
7. Privacy, compatibility, tests, and packaging
   - `includes/class-wc-dhl-privacy.php`
   - `includes/class-wc-dhl-blocks-integration.php`
   - `includes/trait-util.php`
   - `tests/phpunit/`
   - `bin/build-zip.sh`
   - `.github/workflows/`

## Directory Structure

```text
woocommerce-shipping-dhl/
|-- assets/
|   |-- css/
|   `-- js/
|-- bin/
|-- docs/
|-- includes/
|   |-- api/
|   |   |-- class-abstract-address-validator.php
|   |   |-- class-abstract-api-client.php
|   |   `-- rest/
|   |-- data/
|   `-- views/
|-- languages/
|-- tests/
|-- vendor/
|-- README.md
`-- woocommerce-shipping-dhl.php
```

## Current DHL API Surface

The plugin currently uses DHL Express MyDHL API version `2.12.1`.

Implemented endpoint groups:

- Rates
  - `POST /rates`
- Address validation
  - `GET /address-validate`
- Shipment operations
  - `POST /shipments`
  - `POST /pickups`
  - `GET /shipments/{shipmentTrackingNumber}/tracking`
  - `GET /shipments/{shipmentTrackingNumber}/proof-of-delivery`
  - `GET /servicepoints`
  - `POST /landed-cost`

Authentication is Basic Auth using the configured API user and API key. Requests also send the `x-version` header with `WC_SHIPPING_DHL_API_VERSION`.

## Runtime Features

Current implemented features include:

- checkout live rates
- destination address validation
- fallback rates
- per-item, box, and weight-based packing
- admin shipment creation and label persistence
- admin pickup booking
- manual tracking refresh
- scheduled tracking sync
- optional customer-visible tracking notes
- proof-of-delivery retrieval
- service-point lookup tools
- landed-cost estimate tools
- configuration preflight status and fail-closed admin operations
- product-level commodity code management
- redacted DHL debug logging
- lookup caching for selected non-mutating requests
- private document download handling for labels and PODs

## Important Classes

- `WooCommerce\DHL\WC_Shipping_DHL_Init`
  - loads files, registers the shipping method, declares WooCommerce compatibility, and wires blocks/frontend hooks
- `WooCommerce\DHL\Install`
  - runs schema-version checks and settings normalization during admin/plugin init
- `WooCommerce\DHL\WC_Shipping_DHL`
  - owns settings, packing, configuration checks, rate request preparation, and service/rate behavior
- `WooCommerce\DHL\Order_Operations`
  - owns order actions, scheduled tracking sync, tracking state persistence, service-point parsing, landed-cost summaries, and private document serving
- `WooCommerce\DHL\API\REST\API_Client`
  - checkout/rates API client
- `WooCommerce\DHL\API\REST\Shipment_Client`
  - shipment, pickup, tracking, POD, service-point, and landed-cost API client, including selected lookup caching
- `WooCommerce\DHL\API\REST\OAuth`
  - Basic Auth credential/token wrapper used by the REST clients

## Settings and Configuration Model

- The shipping method uses instance settings and normalizes malformed stored arrays on load/save.
- `configuration_status` is a read-only settings field that surfaces missing required configuration directly in the admin UI.
- Missing required fields prevent:
  - checkout live-rate execution
  - shipment creation
  - pickup booking
  - tracking, POD, service-point, and landed-cost admin actions
- Required baseline fields include:
  - API user
  - API key
  - shipper number
  - origin address line
  - origin city
  - origin postcode
  - origin state/region when the origin country requires it

## Plugin Hooks

Filters currently exposed or consumed by the plugin include:

- `woocommerce_dhl_services`
- `woocommerce_shipping_dhl_request`
- `woocommerce_shipping_dhl_rate`
- `woocommerce_dhl_supported_countries`

Actions emitted by the plugin include:

- `woocommerce_dhl_tracking_status_changed`
- `woocommerce_dhl_tracking_customer_notification_sent`
- `woocommerce_dhl_settings_saved`

## Product Data

- DHL commodity codes are stored in `_wc_dhl_commodity_code`.
- The product editor integration supports:
  - simple products
  - product variations
- Values are sanitized before save and then reused by customs and landed-cost flows.

## Caching and Debugging

- Address validation responses are cached when DHL debug mode is off.
- Service-point and landed-cost lookups are cached when DHL debug mode is off.
- Cache keys are fingerprinted from request payload plus credential/environment context.
- Debug logging is structured and redacted before being written to WooCommerce logs.
- Debug mode bypasses lookup caches to make troubleshooting deterministic.

## Development Setup

Install dependencies:

```bash
composer install
npm ci
```

Or use:

```bash
bash bin/setup.sh
```

Set up the WordPress test libraries:

```bash
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
```

Example:

```bash
bash bin/install-wp-tests.sh dhl_plugin_tests root '' 127.0.0.1 latest true
```

## Validation Commands

Run the full local quality gate with:

```bash
composer run-script test
composer run-script phpcs
composer run-script phpstan
vendor/bin/phpcs --extensions=php --standard=.phpcs.security.xml includes woocommerce-shipping-dhl.php bin
npm run build
```

Current verified local baseline:

- PHPUnit: `22 tests, 58 assertions`
- PHPCS: pass
- PHPStan: pass
- Security PHPCS: pass
- Curated ZIP build: pass

## Packaging

Production ZIP creation is handled by:

- `npm run build`
- `bin/build-zip.sh`

The build packages runtime files only and excludes development-only material such as `.github`, `docs`, `tests`, and local tooling.

## Staging and Browser Helpers

- `bin/setup-staging-uat.sh <instance-id>`
  - seeds a DHL instance for repeatable staging UAT
  - creates three synthetic UAT products with commodity codes
- `bin/browser-smoke-settings.sh`
  - browser-level smoke test for DHL settings persistence
  - requires the local Codex Playwright wrapper plus admin credentials, base URL, and DHL instance ID

## CI and Release Workflows

- `.github/workflows/ci.yml`
  - main branch CI for lint, PHPUnit, PHPStan, security PHPCS, and artifact packaging
- `.github/workflows/release.yml`
  - publishes release ZIPs on `v*` tags
- `.github/workflows/qit_runner.yml`
  - reusable QIT workflow against a packaged artifact
- `.github/workflows/qit_manual.yml`
  - manual pre-release entry point for targeted QIT runs
- `.github/workflows/weekly-cron.yml`
  - scheduled weekly QIT run against the packaged artifact

## Current External Constraints

These are current operational constraints, not stale roadmap items:

- live DHL staging validation still requires the current plugin build to be deployed to staging
- valid MyDHL test credentials and shipper details must be entered on staging
- DHL must be attached to the domestic and required international staging zones before the three-lane UAT run
- the plugin does not yet implement invoice upload or shipment document upload flows
- service-point lookup exists as an admin tool, not a checkout selector
- landed-cost estimation exists as an admin tool, not a checkout-time tax/duty UX

## Canonical Status Documents

Use these documents as the source of truth:

- `docs/STATUS.md` for current repo status
- `docs/STAGING_UAT_RUNBOOK.md` for repeatable staging setup
- `docs/LIVE_DHL_UAT_2026-03-07.md` for latest staging validation results
- `docs/API-UPGRADES.md` for API-version maintenance guidance
