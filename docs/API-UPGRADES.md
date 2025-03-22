# DHL API Upgrades Guide

This document provides guidance on handling updates to the DHL Express MyDHL API in the WooCommerce DHL Shipping plugin.

## Current API Version

The plugin currently uses DHL Express MyDHL API version `2.12.1`.

## How API Versioning Works in the Plugin

1. The API version is defined as a constant in `woocommerce-shipping-dhl.php`:
   ```php
   define( 'WC_SHIPPING_DHL_API_VERSION', '2.12.1' );
   ```

2. The API client sends this version in the header of each request:
   ```php
   'x-version' => WC_SHIPPING_DHL_API_VERSION
   ```

## Upgrading the API Version

When DHL releases a new API version:

1. Review the [DHL API Release Notes](https://developer.dhl.com/documentation) for changes
2. Update the `WC_SHIPPING_DHL_API_VERSION` constant in `woocommerce-shipping-dhl.php`
3. Modify the API client classes in `includes/api/rest/` to accommodate any new parameters or endpoints
4. Update tests to verify compatibility
5. Update this document with the new version and any important changes

## Important Considerations

- **Breaking Changes:** Be especially careful with breaking changes in the API
- **New Features:** Document any new features made available through API upgrades
- **Endpoint Changes:** Note if any endpoint URLs have changed
- **Rate Limiting:** Check if rate limiting policies have changed

## Key API Endpoints

The plugin uses the following DHL Express API endpoints:

- **Rates API**: Used to obtain shipping rates
  - Endpoint: `/rates`
  - Method: POST

- **Address Validation**: Used to validate shipping addresses
  - Endpoint: `/address-validate`
  - Method: GET

## API Documentation

For the full API documentation, refer to:
- [DHL Developer Portal](https://developer.dhl.com/)
- [MyDHL API Documentation](https://developer.dhl.com/api-reference/mydhl-api) 