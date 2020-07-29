Wallee Payment for Shopware 6
=============================

The Wallee Payment plugin wraps around the Wallee API. This library facilitates your interaction with various services such as transactions.

## Requirements

- PHP 7.2 and above
- Shopware 6.2

## Installation

You can use **Composer** or **install manually**

### Composer

The preferred method is via [composer](https://getcomposer.org). Follow the
[installation instructions](https://getcomposer.org/doc/00-intro.md) if you do not already have
composer installed.

Once composer is installed, execute the following command in your project root to install this library:

```bash
composer require wallee/shopware-6
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache WalleePayment
```

#### Update via composer
```bash
composer update wallee/shopware-6
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache WalleePayment
```

### Manual Installation

Alternatively you can download the package in its entirety. The [Releases](../../releases) page lists all stable versions.

Uncompress the zip file you download, and include the autoloader in your project:

```bash
# unzip to ShopwareInstallDir/custom/plugins/WalleePayment
composer require wallee/sdk 2.1.1
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache WalleePayment
```

## Usage
The library needs to be configured with your account's space id, user id, and application key which are available in your Wallee
account dashboard.

## Documentation

[Documentation](https://plugin-documentation.wallee.com/wallee-payment/shopware-6/1.1.16/docs/en/documentation.html)

## License

Please see the [license file](https://github.com/wallee-payment/shopware-6/blob/master/LICENSE.txt) for more information.