# DHL Live UAT Report (2026-03-07)

## Summary

Status: **Not production ready**.

This staging execution reached the real external blockers before any live DHL shipment flow could be validated:

1. The active DHL shipping method instance on staging has no `API User`, `API Key`, or `Shipper Number` configured.
2. The DHL zone-instance settings save path is failing on staging:
   - zone modal save requests return `500` from `admin-ajax.php?action=woocommerce_shipping_zone_methods_save_settings`
   - direct instance settings page save returns `500` and renders a critical-error message after claiming the settings were saved

Because the staging method cannot be configured reliably, the remaining carrier-backed UAT flows were **not executable**:

- shipment creation
- pickup booking
- tracking refresh
- proof-of-delivery retrieval
- service-point lookup
- landed-cost estimation

## Environment

- Staging site: `https://laura-johnson-woo.mystagingwebsite.com`
- WordPress admin access: confirmed
- Active plugin: `WooCommerce DHL Shipping` `1.0.0`
- Local repo build reference: commit `0a657b4`
- Local execution date: `2026-03-07`

## What Was Executed

### 1. Staging access and plugin presence

Confirmed:

- WordPress admin login works for the staging site.
- `WooCommerce DHL Shipping` is installed and active.
- WooCommerce is active.

### 2. Shipping-zone inspection

Observed on staging:

- `Continental United States` zone includes `DHL Express`
- `United States (US)` zone includes `DHL Express`
- `Canada` zone does **not** include DHL
- `Europe` zone does **not** include DHL

This means the store was not yet prepared for the planned three-lane UAT coverage even before carrier credential issues were considered.

### 3. Existing DHL instance preflight

The active DHL instance for the domestic zone was inspected before mutation.

Observed defaults on the instance:

- `environment = test`
- `api_user = empty`
- `api_key = empty`
- `shipper_number = empty`
- `origin_addressline = empty`
- `origin_city = empty`
- `origin_state = empty`
- `origin_postcode = empty`
- `dimension_unit = in`
- `weight_unit = LBS`
- `packing_method = per_item`
- `service_point_lookup = off`
- `landed_cost_estimate = off`
- `tracking_sync = off`
- `tracking_customer_notifications = off`
- `debug = off`

### 4. Synthetic validation catalog

Created the three synthetic test products in staging:

- `DHL Test Tee`
  - product ID: `139`
  - SKU: `DHL-TEE`
  - price: `$35.00`
  - product weight: `0.40`
  - dimensions: `30 x 20 x 3`
  - custom field write attempted for `_wc_dhl_commodity_code = 610910` via the admin Custom Fields workflow

- `DHL Test Mug`
  - product ID: `140`
  - SKU: `DHL-MUG`
  - price: `$22.00`
  - product weight: `0.80`
  - dimensions: `12 x 12 x 10`
  - custom field write attempted for `_wc_dhl_commodity_code = 691200` via the admin Custom Fields workflow

- `DHL Test Charger`
  - product ID: `141`
  - SKU: `DHL-CHG`
  - price: `$49.00`
  - product weight: `0.20`
  - dimensions: `10 x 8 x 4`
  - custom field write attempted for `_wc_dhl_commodity_code = 850440` via the admin Custom Fields workflow

This part of the UAT setup succeeded for product creation. The underscore-prefixed DHL commodity meta writes were attempted through WordPress Custom Fields, but were not independently re-read from the post editor after save because WordPress hides underscore-prefixed keys on reload.

## Blocking Failures

### Blocker 1. DHL credentials are missing

The staging DHL method cannot make any live MyDHL API call because the carrier credentials are empty.

Missing values:

- `API User`
- `API Key`
- `Shipper Number`

This alone blocks:

- checkout rates
- create shipment
- pickup booking
- tracking refresh
- proof of delivery
- service points
- landed cost

### Blocker 2. Zone-instance settings save is failing

Attempted to save non-secret DHL settings through the shipping-zone modal.

Observed console/network failure:

- `500` on `wp-admin/admin-ajax.php?action=woocommerce_shipping_zone_methods_save_settings`

Observed result:

- modal returned to the zone screen
- reopening the DHL settings modal showed the values had **not** persisted

Attempted values that did not persist:

- origin address fields
- debug mode
- service-point lookup
- landed-cost estimate
- tracking sync
- tracking notifications

### Blocker 3. Direct instance settings page throws a critical error after save

Attempted to save the same DHL instance through the direct instance page:

- `wp-admin/admin.php?page=wc-settings&tab=shipping&instance_id=13`

Observed result:

- page displayed `Your settings have been saved.`
- page then rendered:
  - `There has been a critical error on this website.`
- browser console logged `500` on the instance page request

This indicates a staging runtime problem on the DHL settings render/save path beyond simple missing credentials.

## Evidence

Playwright snapshots and screenshots captured during the run:

- Product fixture list snapshot:
  - `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/page-2026-03-07T08-02-25-969Z.yml`
- Product fixture list screenshot:
  - `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/page-2026-03-07T08-02-42-677Z.png`
- Critical-error instance page snapshot:
  - `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/page-2026-03-07T08-02-49-826Z.yml`
- Critical-error instance page screenshot:
  - `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/page-2026-03-07T08-02-50-372Z.png`

Relevant console logs:

- `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/console-2026-03-07T08-00-49-363Z.log`
- `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/console-2026-03-07T07-59-24-250Z.log`
- `/Users/mj/Documents/Woo/DHL_live_rates/.playwright-cli/console-2026-03-07T07-57-51-267Z.log`

Key recorded errors:

- `Failed to load resource: the server responded with a status of 500 () @ ...admin-ajax.php?action=woocommerce_shipping_zone_methods_save_settings`
- `Failed to load resource: the server responded with a status of 500 () @ ...admin.php?page=wc-settings&tab=shipping&instance_id=13`

## Not Executed

These steps were not executed because the staging DHL method could not be configured to a valid, stable state:

- lane selection from store order history
- checkout rate validation for domestic/international carts
- placement of UAT orders A/B/C
- create shipment and label
- pickup booking
- manual tracking refresh
- scheduled tracking sync validation
- proof-of-delivery retrieval
- service-point retrieval
- landed-cost retrieval
- WooCommerce DHL log export tied to live API calls

## Recommendation

Recommendation: **do not proceed to production**.

This staging environment must be repaired before live DHL UAT can continue.

## Required Next Actions

1. Fix the DHL shipping-method settings save path on staging.
   - The zone modal save endpoint must stop returning `500`.
   - The direct instance settings page must stop rendering the critical-error message after save.
2. Provide valid MyDHL **test** credentials for the staging DHL instance.
   - `API User`
   - `API Key`
   - `Shipper Number`
3. Re-run the UAT plan after the settings path is stable.
4. Add DHL to the intended international zones before attempting the three-lane checkout run.

Until those four items are resolved, the production-readiness UAT remains blocked.
