# DHL Plugin Status

Date: 2026-03-07
Repository: `/Users/mj/Documents/Woo/DHL_live_rates/woocommerce-shipping-dhl`

## Current Status

Repository status: **Repo-controlled production-readiness backlog executed locally**.

Release status: **Do not approve production cutover yet**. The current build still requires staging deployment and live DHL UAT with valid test credentials.

## Current Truth Sources

1. `docs/STATUS.md`
2. `docs/STAGING_UAT_RUNBOOK.md`
3. `docs/LIVE_DHL_UAT_2026-03-07.md`
4. `docs/PRODUCTION_BACKLOG_2026-03-07.md`
5. `README.md`
6. `docs/DEVELOPER.md`
7. `docs/API-UPGRADES.md`

## Completed In Repository

- DHL settings persistence hardening and array-shape normalization
- Configuration preflight status and fail-closed admin operations
- Commodity-code editing in the product UI for simple products and variations
- Repeatable staging-UAT setup scripts and runbook
- Structured, redacted DHL debug logging
- Safe caching for address validation, service-point, and landed-cost lookups outside debug mode
- Versioned install/migration infrastructure
- Browser-level DHL settings smoke script
- PHPUnit, PHPCS, security PHPCS, PHPStan, curated ZIP build, and updated CI/QIT workflows

The previously observed staging settings-save failure is addressed in code and covered by local regression tests, but it still needs confirmation after the updated build is deployed to staging.

## Local Quality Gate

The current expected local validation commands are:

```bash
composer run-script test
composer run-script phpcs
composer run-script phpstan
vendor/bin/phpcs --extensions=php --standard=.phpcs.security.xml includes woocommerce-shipping-dhl.php bin
npm run build
```

Last verified locally on 2026-03-07:

- `php composer.phar run-script test`: PASS (`22 tests, 58 assertions`)
- `php composer.phar run-script phpcs`: PASS
- `php composer.phar run-script phpstan`: PASS (`[OK] No errors`)
- `vendor/bin/phpcs --extensions=php --standard=.phpcs.security.xml includes woocommerce-shipping-dhl.php bin`: PASS
- `npm run build`: PASS

## Remaining External Steps Before Production

These are not codebase blockers anymore, but they are still required before go-live:

1. Deploy the current plugin build to the staging store.
2. Enter valid MyDHL test credentials and origin data for the target DHL instance.
3. Attach the DHL method to the domestic and required international test zones.
4. Run the live UAT flows from `docs/STAGING_UAT_RUNBOOK.md`:
   - checkout live rates
   - shipment creation
   - pickup booking
   - tracking refresh and scheduled sync
   - proof of delivery retrieval
   - service-point lookup
   - landed-cost estimation
5. Approve production only if the staging evidence in `docs/LIVE_DHL_UAT_2026-03-07.md` is replaced with a full passing run.

## Historical / Superseded Materials

The following older planning/readiness docs were removed because they no longer described the current repository state:

- `docs/CROSS_REPO_REVIEW_2026-02-24.md`
- `docs/PRODUCTION_ROADMAP_2026-02-24.md`
- `docs/RELEASE_READINESS_2026-02-24.md`

Use `docs/PRODUCTION_HARDENING_2026-03-06.md` as a historical record of the earlier hardening pass, not as the current go/no-go document.
