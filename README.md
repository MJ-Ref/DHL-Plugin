# DHL Shipping Live Rates for WooCommerce

This plugin integrates DHL Express live rates and shipment operations with your WooCommerce store.

## Features

- Real-time DHL Express shipping rates at checkout
- Per-item, box-packing, and weight-based packing modes
- DHL service selection with custom names and price adjustments
- Configuration preflight status for missing credentials or origin data
- Destination address validation
- Fallback rates when no live rates are returned
- Product-level DHL commodity code fields for simple products and variations
- Admin order actions to:
  - create shipment + label
  - book pickup
  - refresh tracking
  - fetch proof of delivery (optional)
  - refresh service points (optional)
  - estimate landed cost (optional)
- Scheduled tracking sync via WP-Cron (optional)
- Optional customer-visible order notes for tracking changes
- Private admin-only downloads for labels and proof-of-delivery documents
- Safe lookup caching for address validation, service points, and landed-cost estimates outside debug mode

## Requirements

- WordPress 6.6+
- WooCommerce 9.5+
- PHP 7.4+
- DHL MyDHL API credentials (API user/key) and shipper number

## Installation (Store Owners)

1. In WordPress admin, go to **Plugins -> Add New -> Upload Plugin**.
2. Upload the plugin ZIP and activate it.
3. Go to **WooCommerce -> Settings -> Shipping -> Shipping Zones**.
4. Add **DHL** as a shipping method to your target zones.
5. Open DHL method settings and configure:
   - Environment (`test` or `production`)
   - API User / API Key
   - Shipper Number
   - Origin address and packaging settings
6. Enable any optional features you need:
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
- Maintain DHL commodity codes on products that will be used in customs or landed-cost flows.
- Tracking sync uses WP-Cron and is disabled by default.
- DHL admin shipment tools fail closed when required credentials or origin fields are missing.
- Shipment labels and proof-of-delivery documents are stored privately and downloaded through authenticated admin links.

## Development

Install dependencies:

```bash
composer install
npm ci
```

Or use the helper script:

```bash
bash bin/setup.sh
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
composer run-script phpstan
vendor/bin/phpcs --extensions=php --standard=.phpcs.security.xml includes woocommerce-shipping-dhl.php bin
npm run lint:js
```

Build a production ZIP:

```bash
npm run build
```

## Staging UAT Helpers

Prepare a staging DHL instance and synthetic products with:

```bash
bash bin/setup-staging-uat.sh <instance-id>
```

This seeds:

- test environment defaults
- box-packing configuration and two cartons
- optional DHL feature toggles
- three synthetic UAT products with commodity codes

For browser-level admin smoke testing of DHL settings persistence:

```bash
DHL_SMOKE_BASE_URL=https://example.test \
DHL_SMOKE_ADMIN_USER=admin \
DHL_SMOKE_ADMIN_PASS=secret \
DHL_SMOKE_INSTANCE_ID=123 \
bash bin/browser-smoke-settings.sh
```

Note: the browser smoke helper uses the local Codex Playwright wrapper under `$CODEX_HOME/skills/playwright`.

## CI and Releases

- `main` pushes run lint, PHPUnit, PHPStan, security PHPCS, and package an artifact when checks pass.
- Version tags `v*` publish release ZIPs.
- GitHub Actions includes both manual and weekly QIT workflows against the packaged artifact.

## Documentation

- Current repo status: [`docs/STATUS.md`](docs/STATUS.md)
- Production backlog: [`docs/PRODUCTION_BACKLOG_2026-03-07.md`](docs/PRODUCTION_BACKLOG_2026-03-07.md)
- Staging UAT runbook: [`docs/STAGING_UAT_RUNBOOK.md`](docs/STAGING_UAT_RUNBOOK.md)
- Developer reference: [`docs/DEVELOPER.md`](docs/DEVELOPER.md)
- API upgrade guide: [`docs/API-UPGRADES.md`](docs/API-UPGRADES.md)
- Latest staging UAT report: [`docs/LIVE_DHL_UAT_2026-03-07.md`](docs/LIVE_DHL_UAT_2026-03-07.md)

## Support

For this fork/repository, use GitHub:

- Issues: [Onemoremichael/DHL-Plugin Issues](https://github.com/Onemoremichael/DHL-Plugin/issues)
- Repository: [Onemoremichael/DHL-Plugin](https://github.com/Onemoremichael/DHL-Plugin)

## License

This plugin is licensed under the GNU General Public License v3.0.
