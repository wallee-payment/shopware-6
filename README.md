

wallee Integration for Shopware 6
=============================

## **Overview**  
The wallee Payment Plugin integrates modern payment processing into Shopware 6, offering features like iFrame-based payments, refunds, captures, and PCI compliance. It supports seamless integration with the [wallee Portal](https://app-wallee.com/) for managing transactions and payment methods.

## Requirements

- **Shopware Version:** 6.5.x or 6.6.x (see [compatibility table](#compatibility)).  
- **PHP:** Minimum version as required by your Shopware installation (e.g., 7.4+).  
- **wallee Account:** Obtain `Space ID`, `User ID`, and `API Key` from the [wallee Dashboard](https://app-wallee.com/).

## Documentation

- For English documentation click [here](@WalleeDocPath(/docs/en/documentation.html))
- Für die deutsche Dokumentation klicken Sie [hier](@WalleeDocPath(/docs/de/documentation.html))
- Pour la documentation Française, cliquez [ici](@WalleeDocPath(/docs/fr/documentation.html))
- Per la documentazione in tedesco, clicca [qui](@WalleeDocPath(/docs/it/documentation.html))

## Installation

### **Via Composer (Recommended)**  
1. Navigate to your Shopware root directory.
2. Run:

```bash
Copy
composer require wallee/shopware-6
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache WalleePayment
```

### Manual Installation

1. Download the latest [Release](../../releases)
2. Extract the ZIP to custom/plugins/WalleePayment.

```bash
Copy
bin/console plugin:refresh  
bin/console plugin:install --activate --clearCache WalleePayment  
```

## Configuration
### API Credentials

1. Navigate to Shopware Admin > Settings > Wallee Payment.
2. Enter your Space ID, User ID, and API Key (obtained from the [wallee Portal](https://app-wallee.com/)).

### Payment Methods

Configure supported methods (e.g., credit cards, Apple Pay) via the [wallee Portal](https://app-wallee.com/).

### Key Features
**iFrame Integration**: Embed payment forms directly into your checkout.

**Refunds & Captures**: Trigger full/partial refunds and captures from Shopware or the [wallee Portal](https://app-wallee.com/).

**Multi-Store Support**: Manage configurations across multiple stores.

**Automatic Updates**: Payment methods sync dynamically via the Wallee API.

## Compatibiliity

___________________________________________________________________________________
| Shopware 6 version            | Plugin major version   | Supported until        |
|-------------------------------|------------------------|------------------------|
| Shopware 6.6.x                | 6.x                    | Further notice         |
| Shopware 6.5.x                | 5.x                    | October 2024           |
-----------------------------------------------------------------------------------

### Troubleshooting
**Logs**: Check payment logs with:

```bash
Copy
tail -f var/log/wallee_payment*.log
```
### Common Issues:

Ensure composer update wallee/shopware-6 is run after updates.

Verify API credentials match your Wallee account.

## FAQs
**Q: Does this plugin support one-click payments?**
A: Yes, via tokenization in the Wallee Portal.

**Q: How do I handle PCI compliance?**
A: The plugin uses iFrame integration, reducing PCI requirements to SAQ-A.

### Changelog
For version-specific updates, see the [GitHub Releases](https://github.com/wallee-payment/shopware-6/releases).

### Contributing
Report issues via GitHub Issues.

Follow the Shopware Plugin Base Guide for development.

This template combines technical clarity with user-friendly guidance. For advanced customization (e.g., overriding templates or payment handlers), refer to the Shopware Documentation.

## License

Please see the [license file](https://github.com/wallee-payment/shopware-6/blob/master/LICENSE.txt) for more information.
