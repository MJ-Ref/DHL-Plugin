# DHL API Upgrades Guide

This document describes how to upgrade the plugin when DHL changes the MyDHL API version or behavior.

## Current Version

- Plugin constant: `WC_SHIPPING_DHL_API_VERSION`
- Current value: `2.12.1`
- Defined in: `woocommerce-shipping-dhl.php`

## Current Endpoint Coverage

The current codebase uses these MyDHL surfaces:

- `POST /rates`
- `GET /address-validate`
- `POST /shipments`
- `POST /pickups`
- `GET /shipments/{shipmentTrackingNumber}/tracking`
- `GET /shipments/{shipmentTrackingNumber}/proof-of-delivery`
- `GET /servicepoints`
- `POST /landed-cost`

When upgrading the API version, treat all of those surfaces as part of the compatibility matrix.

## Request Conventions Used by the Plugin

- authentication: Basic Auth header built from DHL API user and API key
- version negotiation: `x-version: WC_SHIPPING_DHL_API_VERSION`
- environments:
  - `https://express.api.dhl.com/mydhlapi` for production
  - `https://express.api.dhl.com/mydhlapi/test` for test

## Upgrade Checklist

1. Review DHL release notes and changelog for the target API version.
2. Update `WC_SHIPPING_DHL_API_VERSION` in `woocommerce-shipping-dhl.php`.
3. Re-verify request/response compatibility in:
   - `includes/api/rest/class-api-client.php`
   - `includes/api/rest/class-address-validator.php`
   - `includes/api/rest/class-shipment-client.php`
4. Recheck endpoint-specific payload assumptions:
   - rate request packaging
   - shipment package construction
   - pickup timing payloads
   - tracking response parsing
   - proof-of-delivery document handling
   - service-point query parameters
   - landed-cost item/breakdown parsing
5. Run the local validation suite:

```bash
composer run-script test
composer run-script phpcs
composer run-script phpstan
vendor/bin/phpcs --extensions=php --standard=.phpcs.security.xml includes woocommerce-shipping-dhl.php bin
npm run build
```

6. Recheck cache and debug assumptions for non-mutating lookups:
   - address validation
   - service-point lookup
   - landed-cost estimates
7. Re-run staging UAT for any endpoint whose schema or semantics changed.
8. Update `README.md`, `docs/DEVELOPER.md`, and `docs/STATUS.md` if behavior or support scope changed.

## High-Risk Change Areas

Pay extra attention to:

- auth/header changes
- renamed products or service codes
- customs/declarable-content schema changes
- tracking response structure changes
- document encoding changes for labels/POD
- landed-cost breakdown type changes
- rate limiting or error-body format changes

## Source References

- DHL Developer Portal: <https://developer.dhl.com/>
- MyDHL API overview: <https://developer.dhl.com/api-reference/mydhl-api-dhl-express>
