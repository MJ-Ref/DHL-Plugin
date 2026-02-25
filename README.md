# DHL Shipping Live Rates for WooCommerce

This plugin integrates DHL Express shipping rates and shipment operations with your WooCommerce store.

## Features

- Real-time DHL Express shipping rates at checkout
- Per-item, box-packing, and weight-based packing modes
- DHL service selection with custom names and price adjustments
- Destination address validation
- Fallback rates when no live rates are returned
- Admin order actions to:
  - Create shipment + label
  - Book pickup
  - Refresh tracking
  - Fetch proof of delivery (optional)
  - Refresh service points (optional)
  - Estimate landed cost (optional)
- Scheduled tracking sync via WP-Cron (optional)
- Optional customer-visible order notes for tracking changes

## Requirements

- WordPress 6.6+
- WooCommerce 9.5+
- PHP 7.4+
- DHL MyDHL API credentials (API user/key) and shipper number

## Installation (Store Owners)

1. In WordPress admin, go to **Plugins → Add New → Upload Plugin**.
2. Upload the plugin ZIP and activate it.
3. Go to **WooCommerce → Settings → Shipping → Shipping Zones**.
4. Add **DHL** as a shipping method to your target zones.
5. Open DHL method settings and configure:
   - Environment (`test` or `production`)
   - API User / API Key
   - Shipper Number
   - Origin address and packaging settings
6. (Optional) Enable advanced operations:
   - Service Points
   - Landed Cost
   - Tracking Sync
   - Tracking Notifications

## Installation (Developers / Source Checkout)

Use this path only when running from source.

```bash
composer install
npm ci
```

## Configuration Notes

- Keep **Environment** on `test` until end-to-end shipment flows are validated.
- Ensure product weights and dimensions are populated for better rate and shipment quality.
- For landed-cost estimates, maintain commodity data where applicable.
- Tracking sync uses WP-Cron and is disabled by default.

## Development

Install dependencies:

```bash
composer install
npm ci
```

Bootstrap WordPress test libraries locally (requires MySQL):

```bash
bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
```

Example (local MySQL root with empty password):

```bash
bash bin/install-wp-tests.sh dhl_plugin_tests root '' 127.0.0.1 latest true
```

Run checks:

```bash
composer run-script test
composer run-script phpcs
vendor/bin/phpcs . --standard=.phpcs.security.xml --ignore=vendor,node_modules
npm run lint:js
npm run lint:css
```

## Support

For plugin support and inquiries:

- Visit the [Help Center](https://woocommerce.com/my-account/create-a-ticket/)
- Email [help@woocommerce.com](mailto:help@woocommerce.com)
- Review [WooCommerce docs](https://docs.woocommerce.com/document/woocommerce-shipping-and-tax/)

## License

This plugin is licensed under the GNU General Public License v3.0.
