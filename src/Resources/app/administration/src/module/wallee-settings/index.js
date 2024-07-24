/* global Shopware */

import './acl';
import './page/wallee-settings';
import './component/sw-wallee-credentials';
import './component/sw-wallee-options';
import './component/sw-wallee-settings-icon';
import './component/sw-wallee-storefront-options';
import './component/sw-wallee-advanced-options';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import itIT from './snippet/it-IT.json';

const {Module} = Shopware;

Module.register('wallee-settings', {
	type: 'plugin',
	name: 'Wallee',
	title: 'wallee-settings.general.descriptionTextModule',
	description: 'wallee-settings.general.descriptionTextModule',
	color: '#28d8ff',
	icon: 'default-action-settings',
	version: '1.0.1',
	targetVersion: '1.0.1',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
        'fr-FR': frFR,
        'it-IT': itIT,
    },

	routes: {
		index: {
			component: 'wallee-settings',
			path: 'index',
			meta: {
				parentPath: 'sw.settings.index',
				privilege: 'wallee.viewer'
			},
			props: {
                default: (route) => {
                    return {
                        hash: route.params.hash,
                    };
                },
            },
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
