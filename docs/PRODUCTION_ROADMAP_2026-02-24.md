# WooCommerce DHL Shipping: Production Roadmap

Date: 2026-02-24

## Current baseline in this repo

- Live DHL rate quoting with MyDHL `/rates`.
- Packaging modes: per-item, box packing, and weight-based.
- Service filtering and per-service price adjustments.
- Destination address validation and fallback rate support.
- Admin order actions for:
  - shipment creation + label persistence,
  - pickup booking,
  - tracking refresh.

## Research snapshot (as of 2026-02-24)

- DHL MyDHL API currently exposes rates, products, shipments, pickups, tracking, landed cost, invoice upload, and proof-of-delivery capability surfaces.
- DHL Location Finder Unified API supports service-point discovery (pickup/drop-off locations).
- Official DHL-for-Woo plugin positioning emphasizes labels, pickup scheduling, tracking status visibility, and delivery preference options.
- WooCommerce extension guidance requires explicit compatibility declarations (HPOS and Product Editor) for modern extension readiness.
- Woo Quality Insights Testing (QIT) defines managed validation/security/compatibility suites expected for release hardening.

## Features this plugin should have for production

### P0 (must-have before wide release)

- Stable rate quoting with resilient fallback behavior.
- Shipment creation and label generation from order admin.
- Pickup booking from order admin.
- Tracking refresh with order note updates and stored tracking IDs.
- CI and release gates:
  - PHP lint
  - PHPUnit matrix
  - security scan
  - QIT suite

### P1 (high impact next)

- Customs workflow completion:
  - invoice data upload,
  - shipment image/document upload,
  - stronger declarable-content validation.
- Automated tracking sync (scheduled refresh) and optional customer notification hooks.
- Proof-of-delivery retrieval for completed shipments.
- Service-point support (`/servicepoints`) at checkout/admin.

### P2 (conversion and global checkout optimization)

- Landed-cost estimate option (`/landed-cost`) for duties/taxes visibility.
- Delivery promise/ETA surfacing where API data supports it.
- Rules engine for lane/value/product-based service filtering.
- Return-label and reverse-logistics workflows.

## Recommended delivery plan

1. Phase 1 (now): finalize code quality and release workflows, then validate in staging with live DHL credentials.
2. Phase 2: complete customs docs + automated tracking sync + proof-of-delivery retrieval.
3. Phase 3: launch service-point and landed-cost experiences.
4. Phase 4: add return logistics and conversion optimization features.

## Sources

- DHL MyDHL API catalog: https://developer.dhl.com/api-reference/dhl-express-mydhl-api
- DHL Location Finder Unified API catalog: https://developer.dhl.com/api-reference/dhl-location-finder-unified-api
- Official DHL for WooCommerce plugin page: https://wordpress.org/plugins/dhl-for-woocommerce/
- WooCommerce compatibility declarations (HPOS/Product Editor): https://developer.woocommerce.com/docs/extensions/getting-started/extension-development-overview#declaring-compatible-features
- Woo Quality Insights Testing docs: https://developer.woocommerce.com/docs/features/quality-insights-testing/get-started/
