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

### Automatic Installation
1. Log in to your WordPress dashboard
2. Navigate to Plugins → Add New
3. Search for "WooCommerce DHL Shipping"
4. Click "Install Now" and then "Activate"

### Manual Installation
1. Download the plugin zip file
2. Log in to your WordPress dashboard
3. Navigate to Plugins → Add New
4. Click "Upload Plugin"
5. Upload the zip file
6. Activate the plugin

### Post-Installation
1. After installation, run the following commands in the plugin directory to install dependencies:
   ```
   composer install
   ```
2. Navigate to WooCommerce → Settings → Shipping → Shipping Zones
3. Add DHL as a shipping method to your desired shipping zones
4. Configure the DHL settings with your API credentials and shipping preferences

### Dependencies
This plugin requires the following dependencies:
- PHP 7.4 or higher
- WooCommerce 9.5 or higher
- WordPress 6.6 or higher
- WooCommerce BoxPacker library (installed automatically via Composer)

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

For plugin support and inquiries:
- Visit our [Help Center](https://woocommerce.com/my-account/create-a-ticket/)
- Email us at [help@woocommerce.com](mailto:help@woocommerce.com)
- Visit our [documentation](https://docs.woocommerce.com/document/woocommerce-shipping-and-tax/)

## License

This plugin is licensed under the GNU General Public License v3.0.