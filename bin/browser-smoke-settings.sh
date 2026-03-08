#!/usr/bin/env bash

set -euo pipefail

if ! command -v npx >/dev/null 2>&1; then
	echo "Error: npx is required to run the Playwright CLI wrapper."
	exit 1
fi

: "${DHL_SMOKE_BASE_URL:?Set DHL_SMOKE_BASE_URL to the site base URL}"
: "${DHL_SMOKE_ADMIN_USER:?Set DHL_SMOKE_ADMIN_USER to a WordPress admin username}"
: "${DHL_SMOKE_ADMIN_PASS:?Set DHL_SMOKE_ADMIN_PASS to the WordPress admin password}"
: "${DHL_SMOKE_INSTANCE_ID:?Set DHL_SMOKE_INSTANCE_ID to the DHL shipping method instance ID}"

export CODEX_HOME="${CODEX_HOME:-$HOME/.codex}"
export PWCLI="${CODEX_HOME}/skills/playwright/scripts/playwright_cli.sh"
export PLAYWRIGHT_CLI_SESSION="${PLAYWRIGHT_CLI_SESSION:-dhl-settings-smoke}"

mkdir -p output/playwright

"$PWCLI" open "${DHL_SMOKE_BASE_URL%/}/wp-login.php"
"$PWCLI" run-code "await page.fill('#user_login', process.env.DHL_SMOKE_ADMIN_USER); await page.fill('#user_pass', process.env.DHL_SMOKE_ADMIN_PASS); await page.click('#wp-submit'); await page.waitForLoadState('networkidle');"
"$PWCLI" run-code "const baseUrl = process.env.DHL_SMOKE_BASE_URL.replace(/\\/$/, ''); await page.goto(baseUrl + '/wp-admin/admin.php?page=wc-settings&tab=shipping&instance_id=' + process.env.DHL_SMOKE_INSTANCE_ID, { waitUntil: 'networkidle' });"
"$PWCLI" run-code "const setValue = async (suffix, value) => { const field = page.locator('[id$=\"_' + suffix + '\"]').first(); await field.fill(value); }; await setValue('api_user', 'smoke-user'); await setValue('origin_addressline', '123 Smoke Street'); await setValue('origin_city', 'New York'); await setValue('origin_state', 'NY'); await setValue('origin_postcode', '10001'); await page.locator('[id$=\"_origin_country\"]').first().selectOption('US'); await page.locator('[id$=\"_packing_method\"]').first().selectOption('box_packing'); await page.locator('[id$=\"_debug\"]').first().check();"
"$PWCLI" run-code "await page.locator('button.woocommerce-save-button, button[name=\"save\"]').first().click(); await page.waitForLoadState('networkidle');"
"$PWCLI" run-code "await page.reload({ waitUntil: 'networkidle' }); const checks = [ ['api_user', 'smoke-user'], ['origin_addressline', '123 Smoke Street'], ['origin_city', 'New York'], ['origin_state', 'NY'], ['origin_postcode', '10001'] ]; for (const [suffix, expected] of checks) { const actual = await page.locator('[id$=\"_' + suffix + '\"]').first().inputValue(); if (actual !== expected) { throw new Error('Persistence check failed for ' + suffix + ': expected ' + expected + ' but found ' + actual); } } const packing = await page.locator('[id$=\"_packing_method\"]').first().inputValue(); if (packing !== 'box_packing') { throw new Error('Expected packing method box_packing but found ' + packing); }"
"$PWCLI" run-code "await page.screenshot({ path: 'output/playwright/dhl-settings-smoke.png', fullPage: true });"
"$PWCLI" close

echo "Browser smoke succeeded. Screenshot: output/playwright/dhl-settings-smoke.png"
