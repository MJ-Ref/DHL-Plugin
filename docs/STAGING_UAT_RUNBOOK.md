# DHL Staging UAT Runbook

Repository: `/Users/mj/Documents/Woo/DHL_live_rates/woocommerce-shipping-dhl`  
Status source: `docs/STATUS.md`

## Goal

Prepare a staging WooCommerce site for repeatable DHL UAT with the same:

- DHL instance feature toggles
- packing mode and carton definitions
- synthetic products
- order scenarios

This runbook intentionally does **not** write carrier credentials or mutate shipping zones automatically.

## Preconditions

- WooCommerce is active.
- The DHL plugin build from this repo is installed.
- A DHL shipping method instance already exists in the target zone.
- WP-CLI is available on the staging host.
- You know the DHL `instance_id` for the target zone method.

## 1. Seed the DHL instance and fixtures

From the WordPress root:

```bash
bash wp-content/plugins/woocommerce-shipping-dhl/bin/setup-staging-uat.sh <instance-id>
```

What this does:

- sets `environment = test`
- enables:
  - `service_point_lookup`
  - `landed_cost_estimate`
  - `tracking_sync`
  - `tracking_customer_notifications`
  - `debug`
- sets:
  - `packing_method = box_packing`
  - `dimension_unit = cm`
  - `weight_unit = KG`
- writes two cartons:
  - `DHL Small Carton` `30 x 22 x 10 cm`, `0.15 kg`, max `2.0 kg`
  - `DHL Medium Carton` `40 x 30 x 20 cm`, `0.25 kg`, max `5.0 kg`
- creates/updates three hidden test products by SKU:
  - `DHL-TEE`
  - `DHL-MUG`
  - `DHL-CHG`

## 2. Manually complete the non-automated settings

Open:

`WooCommerce -> Settings -> Shipping -> DHL instance`

Fill in:

- `API User`
- `API Key`
- `Shipper Number`
- `Origin Address Line`
- `Origin City`
- `Origin State / Province` when required
- `Origin Country`
- `Origin Postcode`

Do not switch to production during UAT.

## 3. Verify shipping-zone coverage

The plugin must be attached to the lanes you plan to test.

Minimum expected lanes:

- one domestic zone
- two DHL-serviceable international zones

For the earlier staging target, DHL was missing on `Canada` and `Europe`, so those zones must be updated before the three-lane checkout run.

## 4. Confirm seeded products

Products expected after the seed script:

1. `DHL Test Tee`
   - SKU `DHL-TEE`
   - `35.00`
   - `0.40 kg`
   - `30 x 20 x 3 cm`
   - commodity code `610910`
2. `DHL Test Mug`
   - SKU `DHL-MUG`
   - `22.00`
   - `0.80 kg`
   - `12 x 12 x 10 cm`
   - commodity code `691200`
3. `DHL Test Charger`
   - SKU `DHL-CHG`
   - `49.00`
   - `0.20 kg`
   - `10 x 8 x 4 cm`
   - commodity code `850440`

## 5. Reuse the same checkout scenarios

Use frontend checkout, not admin-created orders.

1. Order A / domestic
   - `1x DHL Test Tee`
   - `1x DHL Test Charger`
2. Order B / international
   - `2x DHL Test Mug`
3. Order C / international mixed
   - `1x DHL Test Tee`
   - `1x DHL Test Mug`
   - `1x DHL Test Charger`

## 6. Run the operational actions

For each DHL order:

1. `DHL: Create Shipment & Label`
2. `DHL: Book Pickup`
3. `DHL: Refresh Tracking`

For relevant international orders:

4. `DHL: Refresh Service Points`
5. `DHL: Estimate Landed Cost`

For delivered test shipments:

6. `DHL: Fetch Proof of Delivery`

## 7. Evidence to capture

- order IDs
- tracking numbers
- pickup confirmation numbers
- label and POD download checks
- WooCommerce DHL logs
- screenshots of:
  - checkout DHL rates
  - order actions
  - private document downloads

## Exit Criteria

The staging setup is considered repeatable when a second operator can:

1. run the seed script
2. fill only credentials/origin/zone assignment manually
3. produce the same three products and DHL settings state
4. run the same three checkout scenarios without ad hoc setup work
