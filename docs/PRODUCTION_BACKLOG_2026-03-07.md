# DHL Production-Readiness Backlog

Date: 2026-03-07
Repository: `/Users/mj/Documents/Woo/DHL_live_rates/woocommerce-shipping-dhl`

## Scope

This backlog includes only work that is under our control:

- plugin code
- staging store configuration and repeatability
- test coverage and QA automation
- release engineering and repo tooling
- merchant/admin UX and operational safety

This backlog explicitly excludes work that depends on DHL changing or fixing anything on their side.

## Current Context

Current repo-controlled backlog status: **executed locally**.

Production cutover status: **still blocked pending staging deployment and live DHL UAT**.

Primary evidence:

- `docs/STATUS.md`
- `docs/LIVE_DHL_UAT_2026-03-07.md`
- `docs/PRODUCTION_HARDENING_2026-03-06.md`

## Excluded From This Backlog

These items are intentionally out of scope here:

- DHL issuing credentials faster
- DHL changing sandbox behavior or account restrictions
- DHL correcting endpoint behavior or coverage
- DHL improving service-point data quality or landed-cost accuracy
- DHL carrier-side changes to tracking, POD, or pickup acceptance rules

## Execution Summary

All repo-controlled backlog items were executed in the current codebase. The remaining work is operational:

1. deploy the current build to staging
2. enter valid MyDHL test credentials
3. attach DHL to the required staging zones
4. rerun live UAT

## Completed Backlog Items

### P0.1 Settings save/render hardening

Status: complete

Evidence:

- `includes/class-wc-shipping-dhl.php`
- `includes/views/html-box-packing.php`
- `includes/views/html-services.php`
- `includes/views/html-services-table.php`
- `tests/phpunit/class-wc-shipping-dhl-test.php`

Delivered:

- normalized loading and persistence for complex instance settings
- custom validators for box and service tables
- settings-save regression coverage for instance fields

### P0.2 Settings persistence regression coverage

Status: complete

Evidence:

- `tests/phpunit/class-wc-shipping-dhl-test.php`

Delivered:

- instance settings save coverage
- malformed array normalization coverage
- configuration error coverage

### P0.3 Configuration preflight and fail-closed behavior

Status: complete

Evidence:

- `includes/class-wc-shipping-dhl.php`
- `includes/class-order-operations.php`
- `includes/class-wc-shipping-dhl-admin.php`

Delivered:

- read-only configuration status field in settings
- instance-aware configuration notices
- checkout and DHL admin actions blocked when required data is missing

### P0.4 Repeatable staging UAT setup

Status: complete

Evidence:

- `bin/setup-staging-uat.php`
- `bin/setup-staging-uat.sh`
- `docs/STAGING_UAT_RUNBOOK.md`

Delivered:

- repeatable DHL instance seeding for staging
- synthetic product creation with commodity codes
- documented checkout lanes and order scenarios

### P0.5 Commodity-code editing in the product UI

Status: complete

Evidence:

- `includes/class-product-editor.php`
- `tests/phpunit/class-product-editor-test.php`

Delivered:

- supported commodity-code fields for simple products and variations
- sanitization and persistence tests

### P1.1 PHPStan and static-analysis gating

Status: complete

Evidence:

- `composer.json`
- `phpstan.neon.dist`
- `phpstan-stubs.php`
- `.github/workflows/ci.yml`

Delivered:

- local `composer run-script phpstan`
- PHPStan in CI
- WooCommerce/WordPress stubs for analysis

### P1.2 Reusable QIT workflow coverage

Status: complete

Evidence:

- `.github/workflows/qit_runner.yml`
- `.github/workflows/qit_manual.yml`

Delivered:

- reusable QIT runner against packaged artifacts
- manual pre-release workflow entry point

### P1.3 PHPCS ruleset cleanup

Status: complete

Evidence:

- `composer.json`
- `composer.lock`
- `.phpcs.security.xml`
- `phpcs.xml.dist`

Delivered:

- upgraded PHPCS-related dependencies
- updated rulesets for current sniffs
- warning-free local PHPCS run

### P1.4 Versioned migration/install infrastructure

Status: complete

Evidence:

- `includes/class-install.php`
- `includes/class-wc-shipping-dhl-init.php`

Delivered:

- schema-version option
- admin-time migration entry point
- normalization path for existing DHL settings

### P1.5 Admin navigation and support ergonomics

Status: complete

Evidence:

- `includes/class-wc-shipping-dhl-admin.php`

Delivered:

- plugin action links
- plugin row meta links
- repo-specific docs/support/status navigation

### P2.1 Safe request caching for non-mutating lookups

Status: complete

Evidence:

- `includes/api/rest/class-address-validator.php`
- `includes/api/rest/class-shipment-client.php`

Delivered:

- cached address validation
- cached service-point lookups
- cached landed-cost estimates
- request fingerprinting scoped by payload plus credential/environment context
- automatic cache bypass in debug mode

### P2.2 Debug observability and redaction

Status: complete

Evidence:

- `includes/class-wc-shipping-dhl.php`
- `includes/api/rest/class-shipment-client.php`

Delivered:

- structured DHL debug logging
- sensitive-value masking and payload redaction

### P2.3 Browser-level smoke coverage

Status: complete

Evidence:

- `bin/browser-smoke-settings.sh`

Delivered:

- browser-level persistence smoke for DHL instance settings
- screenshot artifact output under `output/playwright/`

### P2.4 Advanced-feature UX hardening

Status: complete

Evidence:

- `includes/class-wc-shipping-dhl.php`
- `includes/class-wc-shipping-dhl-admin.php`

Delivered:

- clearer separation between checkout settings and shipment tools
- configuration-status surfacing in settings and admin notices
- less ambiguous operator path for advanced DHL features

## Production Gate Definition For This Backlog

From our side, this backlog is complete when all of the following are true:

1. DHL settings can be saved and reloaded reliably in the zone-instance UI.
2. Required configuration problems are surfaced before runtime.
3. Staging/UAT setup is repeatable and documented.
4. Commodity code entry is supported in the product UI.
5. Local quality gate includes PHPUnit, PHPCS, security PHPCS, build, and static analysis.
6. QIT/release workflows are strong enough to validate packaged artifacts.
7. Admin/settings regressions are covered by automated tests beyond pure unit coverage.

The codebase now satisfies that backlog definition locally. Production remains gated only by staging deployment and live DHL validation.
