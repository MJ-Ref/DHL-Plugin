# DHL Live Rates Production Hardening Report (2026-03-06)

## Scope

This pass addressed the production-readiness gaps found during the March 2026 review:

- Tracking sync queue starvation and unconditional cron scheduling.
- Shipment payload package construction diverging from checkout packing logic.
- Unsafe fallback to default DHL settings when an order's original instance is missing.
- Public exposure of shipment labels and proof-of-delivery documents.
- Shipping-zone modal asset loading gaps.
- Release packaging shipping a source snapshot instead of a curated plugin distribution.

## Changes Applied

- Reworked scheduled tracking sync to:
  - run only when at least one DHL instance enables tracking sync,
  - build a stable queue from tracked orders sorted by their last sync timestamp,
  - store an integer tracking sync timestamp for deterministic scheduling.
- Updated order operations to fail closed when a DHL shipping instance cannot be resolved.
- Rebuilt shipment package generation so shipment, pickup, service-point, and landed-cost flows reuse configured packing behavior from the shipping method.
- Fixed per-item packing to emit one package per unit quantity.
- Moved label and POD access behind authenticated admin download endpoints and removed new public document URLs from order metadata.
- Broadened DHL admin asset loading to the WooCommerce shipping tab so the zone-instance modal loads the required scripts and styles.
- Replaced the `git archive` build with a curated ZIP build script that packages runtime files only.
- Updated the reusable GitHub build workflow to install production PHP dependencies before packaging.

## Verification

- `php composer.phar run-script test`: PASS (`17 tests, 43 assertions`)
- `php composer.phar run-script phpcs`: PASS
- `vendor/bin/phpcs . --standard=.phpcs.security.xml --ignore=vendor,node_modules`: PASS
- `npm run build`: PASS
- Curated ZIP verified at `woocommerce-shipping-dhl.zip`
  - includes runtime plugin files only
  - excludes `.github`, `docs`, `tests`, `bin`, and project metadata not needed in production

## Remaining Required UAT

These checks still require live DHL staging or production credentials and a real WooCommerce store:

- End-to-end shipment creation for representative domestic and international carts.
- Pickup booking acceptance with real shipper account rules and pickup windows.
- Tracking refresh against active DHL tracking numbers.
- Proof-of-delivery retrieval for delivered shipments.
- Service-point search quality in target geographies.
- Landed-cost estimate accuracy for carts with real commodity-code data.
- Admin download flow for private label/POD documents in the target hosting environment.

## Status

Status: Codebase hardened and ready for staging UAT.

Production cutover should happen only after the live DHL endpoint validations above pass.
