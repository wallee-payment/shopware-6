// Import all necessary Storefront plugins and scss files
import WalleeCheckoutPlugin
    from './wallee-checkout-plugin/wallee-checkout-plugin.plugin';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register(
    'WalleeCheckoutPlugin',
    WalleeCheckoutPlugin,
    '[data-wallee-checkout-plugin]'
);

if (module.hot) {
    // noinspection JSValidateTypes
    module.hot.accept();
}