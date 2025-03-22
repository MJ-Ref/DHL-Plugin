# WooCommerce DHL Shipping - Developer Documentation

This document provides technical information for developers working with the WooCommerce DHL Shipping plugin.

## Architecture

The plugin follows an object-oriented approach with a clean separation of concerns:

1. **Main Plugin Class** (`WC_Shipping_DHL_Init`): Initializes the plugin and sets up hooks.
2. **Shipping Method Class** (`WC_Shipping_DHL`): Implements the WooCommerce shipping method interface.
3. **API Layer**: 
   - Abstract classes for API interactions and address validation
   - Concrete implementations for REST API
4. **Utility Classes**: Logger, Notifier, and a utility trait for common functions.
5. **Views**: Templates for the admin interface.

## Directory Structure

```
woocommerce-shipping-dhl/
├── assets/                     # Frontend and admin assets
│   ├── css/                    # Stylesheets
│   └── js/                     # JavaScript files
├── docs/                       # Documentation
├── includes/                   # PHP classes
│   ├── api/                    # API integration classes
│   │   ├── rest/               # REST API implementation
│   │   └── legacy/             # Reserved for legacy API implementations
│   ├── data/                   # Data files
│   └── views/                  # Admin view templates
├── languages/                  # Translation files
└── vendor/                     # Composer dependencies (after install)
```

## Key Classes and Files

- `woocommerce-shipping-dhl.php`: Main plugin file with constants and initialization
- `includes/class-wc-shipping-dhl-init.php`: Plugin initialization class
- `includes/class-wc-shipping-dhl.php`: WooCommerce shipping method implementation
- `includes/api/class-abstract-api-client.php`: Abstract API client with common methods
- `includes/api/rest/class-api-client.php`: DHL REST API implementation
- `includes/api/rest/class-oauth.php`: Authentication handler
- `includes/api/rest/class-address-validator.php`: Address validation implementation
- `includes/class-logger.php`: Logging functionality
- `includes/class-notifier.php`: Admin notifications
- `includes/trait-util.php`: Utility functions

## APIs and Endpoints

The plugin integrates with DHL Express MyDHL API (v2.12.1). The main endpoints used are:

### Rate API
- Endpoint: `https://express.api.dhl.com/mydhlapi/rates`
- Method: POST
- Authentication: Basic Auth
- Documentation: [DHL Express MyDHL API](https://developer.dhl.com/api-reference/dhl-express-mydhl-api-rate)

### Address Validation API
- Endpoint: `https://express.api.dhl.com/mydhlapi/address-validate`
- Method: GET
- Authentication: Basic Auth
- Documentation: [DHL Express Address Validation](https://developer.dhl.com/api-reference/dhl-express-mydhl-api-address)

## Authentication

The plugin uses Basic Authentication with DHL Express API credentials. The authentication flow:

1. Customer enters API User and API Key in the settings
2. Credentials are encoded as Base64 for Basic Authentication
3. The encoded token is cached for 23 hours to minimize API calls

## Packing Methods

The plugin supports three packing methods:

1. **Per Item**: Each item is shipped individually
2. **Box Packing**: Items are packed into user-defined boxes using the WooCommerce BoxPacker library
3. **Weight-Based**: Calculate shipping based on total order weight

## Filters and Actions

### Filters

- `woocommerce_dhl_services`: Modify the list of available DHL services
- `woocommerce_shipping_dhl_request`: Modify the request before sending to DHL API
- `woocommerce_shipping_dhl_rate`: Modify shipping rates returned from DHL
- `woocommerce_dhl_supported_countries`: Modify the list of countries supported by DHL

### Actions

No specific actions are exposed by the plugin, but it hooks into WooCommerce's standard shipping actions.

## Development Setup

1. Clone the repository
2. Install dependencies:
   ```
   composer install
   ```
3. For frontend assets, the plugin uses basic JavaScript and CSS without a build system. If you add a build system:
   ```
   npm install
   npm run build
   ```

## Testing

The plugin doesn't currently include automated tests. When adding tests:

1. Run PHPUnit tests:
   ```
   composer test
   ```
2. Check code standards:
   ```
   composer phpcs
   ```
3. Autofix code standards issues:
   ```
   composer phpcbf
   ```

## BoxPacker Integration

The plugin uses WooCommerce's BoxPacker library for efficient packing of items into boxes. Key integration points:

1. `get_box_packer()`: Creates a BoxPacker instance
2. `get_shipping_boxes()`: Gets the user-defined boxes for packing
3. `box_shipping()`: Executes the box packing algorithm and prepares packages

## Known Limitations

1. The plugin currently only supports DHL Express services, not DHL eCommerce or other DHL divisions
2. Address validation is optional and may not work in all countries
3. The plugin doesn't provide shipping label generation (future enhancement)

## Additional Resources

- [DHL Express API Documentation](https://developer.dhl.com/api-reference/dhl-express-mydhl-api)
- [WooCommerce Shipping Development](https://woocommerce.github.io/code-reference/classes/WC-Shipping-Method.html)
- [BoxPacker Library](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/packages/woocommerce-packages-boxpacker)