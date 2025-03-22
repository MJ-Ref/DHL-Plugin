# WooCommerce DHL Shipping

This plugin integrates DHL Express shipping rates and services with your WooCommerce store.

## Features

- Real-time DHL Express shipping rates
- Box packing and weight-based shipping options
- Multiple DHL Express services support
- Address validation
- Configurable price adjustments
- Custom box definitions
- Fallback rates

## Installation

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-shipping-dhl` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to WooCommerce > Settings > Shipping > Shipping Zones
4. Add DHL as a shipping method to your desired shipping zones
5. Configure the DHL settings with your API credentials and shipping preferences

## Configuration

### API Settings

You will need DHL Express API credentials to use this plugin:

1. Contact DHL to obtain your MyDHL API credentials (API User and API Key)
2. Enter your API credentials in the plugin settings
3. Configure your shipper account number

### Origin Settings

Set your shipping origin details:

- Address
- City
- State
- Country
- Postcode

### Packaging Settings

Configure how your products will be packed:

- Per item (each item shipped individually)
- Box packing (items packed into defined boxes)
- Weight-based (calculate shipping based on total order weight)

### Service Settings

Select and customize the DHL services you want to offer:

- Enable/disable specific services
- Set custom names for services
- Apply percentage or fixed amount price adjustments

## Requirements

- WordPress 6.6+
- WooCommerce 9.5+
- PHP 7.4+

## Support

For support, please contact [support@yourwebsite.com](mailto:support@yourwebsite.com).

## License

This plugin is licensed under the GNU General Public License v3.0.