/* global Shopware */

import './acl';
import './page/wallee-settings';
import './component/sw-wallee-credentials';
import './component/sw-wallee-options';
import './component/sw-wallee-settings-icon';
import './component/sw-wallee-storefront-options';
import './component/sw-wallee-advanced-options';

const {Module} = Shopware;

Module.register('wallee-settings', {
	type: 'plugin',
	name: 'Wallee',
	title: 'wallee-settings.general.descriptionTextModule',
	description: 'wallee-settings.general.descriptionTextModule',
	color: '#28d8ff',
	icon: 'default-action-settings',
	version: '1.0.0',
	targetVersion: '1.0.0',

	routes: {
		index: {
			component: 'wallee-settings',
			path: 'index',
			meta: {
				parentPath: 'sw.settings.index',
				privilege: 'wallee.viewer'
			}
		}
	},

	settingsItem: {
		group: 'plugins',
		to: 'wallee.settings.index',
		iconComponent: 'sw-wallee-settings-icon',
		backgroundEnabled: true,
		privilege: 'wallee.viewer'
	}

});
