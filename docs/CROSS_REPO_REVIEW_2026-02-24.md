# DHL Live Rates Workspace Review

Date: 2026-02-24
Workspace: `/Users/mj/Documents/Woo/DHL_live_rates`

## Repositories and docs reviewed

- `/Users/mj/Documents/Woo/DHL_live_rates/woocommerce-shipping-dhl`
- `/Users/mj/Documents/Woo/DHL_live_rates/woocommerce-shipping-ups-trunk`
- `/Users/mj/Documents/Woo/DHL_live_rates/woocommerce-shipping-usps-trunk`
- `/Users/mj/Documents/Woo/DHL_live_rates/DHL DOcs/dpdhl-express-api-2.12.1_swagger_0.yaml`
- `/Users/mj/Documents/Woo/DHL_live_rates/ExpressReferenceData_2.12.0.xlsx`

## Cross-repo snapshot

| Area | DHL | UPS | USPS |
| --- | --- | --- | --- |
| Live rates at checkout | Yes (MyDHL `/rates`) | Yes | Yes |
| Packaging modes | Per-item, box, weight | Per-item, box, weight | Per-item, box, flat-rate options |
| Service-level config | Yes | Yes | Yes |
| Address validation | Yes (destination) | Yes | Limited/flow-specific |
| Carrier-specific pricing controls | Basic adjustments | Negotiated + simple-rate controls | USPS-specific service/flat-rate controls |
| Shipment label creation | Implemented (admin order action, new scaffold) | Out of scope in this extension | Out of scope in this extension |
| Pickup booking | Implemented (admin order action, new scaffold) | Out of scope in this extension | Out of scope in this extension |
| Tracking sync | Implemented (admin order action, new scaffold) | Out of scope in this extension | Out of scope in this extension |
| QIT workflows | Present | Present | Present |
| Update-requires headers workflow | Added for parity | Present | Present |

## CI/release parity findings

- UPS and USPS both include:
  - build workflow
  - merge-to-trunk workflow
  - weekly QIT workflow
  - WC release smoke workflow
  - update-requires-headers workflow
- DHL now follows the same release posture and includes CI matrix coverage for PHP/WP/Woo combinations plus Woo install during tests.

## DHL API coverage vs available MyDHL endpoints

From local DHL 2.12.1 OpenAPI docs, currently available endpoints include:

- Implemented now in plugin:
  - `/rates`
  - `/shipments` (create)
  - `/pickups` (create)
  - `/shipments/{shipmentTrackingNumber}/tracking`
- Not yet implemented in plugin:
  - `/servicepoints`
  - `/landed-cost`
  - `/tracking` (multi-query)
  - `/shipments/{shipmentTrackingNumber}/proof-of-delivery`
  - `/shipments/{shipmentTrackingNumber}/upload-image`
  - `/shipments/{shipmentTrackingNumber}/upload-invoice-data`
  - `/invoices/upload-invoice-data`

## Production-readiness status

Completed in DHL repo:

- Shipment operations scaffold (shipment, pickup, tracking clients + admin order actions).
- Label persistence in uploads and order metadata.
- HPOS/product editor compatibility declarations.
- QIT and smoke workflow alignment with sibling repos.
- PHPCS + security PHPCS clean under current project standards.

Remaining high-value gaps:

- Automated tracking refresh (scheduled sync + customer events).
- Commercial invoice/customs document upload flow for customs-heavy lanes.
- Service-point selection in checkout and order storage.
- Landed-cost duties/taxes estimate option at checkout.
- End-to-end live API tests with non-sandbox DHL credentials in staging.
