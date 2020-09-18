/* global Shopware */

import './extension/sw-plugin';
import './extension/sw-settings-index';
import './page/wallee-settings';
import './component/sw-wallee-credentials';
import './component/sw-wallee-options';
import './component/sw-wallee-storefront-options';
import enGB from './snippet/en-GB.json';
import deDE from './snippet/de-DE.json';

const {Module} = Shopware;

Module.register('wallee-settings', {
	type: 'plugin',
	name: 'Wallee',
	title: 'wallee-settings.general.descriptionTextModule',
	description: 'wallee-settings.general.descriptionTextModule',
	color: '#62ff80',
	icon: 'default-action-settings',

	snippets: {
		'de-DE': deDE,
		'en-GB': enGB
	},

	routes: {
		index: {
			component: 'wallee-settings',
			path: 'index',
			meta: {
				parentPath: 'sw.settings.index'
			}
		}
	}

});
