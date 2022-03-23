/* global Shopware */

import './extension/sw-order';
import './page/wallee-order-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import itIT from './snippet/it-IT.json';

const {Module} = Shopware;

Module.register('wallee-order', {
	type: 'plugin',
	name: 'Wallee',
	title: 'wallee-order.general.title',
	description: 'wallee-order.general.descriptionTextModule',
	version: '1.0.0',
	targetVersion: '1.0.0',
	color: '#2b52ff',

	snippets: {
		'de-DE': deDE,
		'en-GB': enGB,
		'fr-FR': frFR,
		'it-IT': itIT
	},

	routeMiddleware(next, currentRoute) {
		if (currentRoute.name === 'sw.order.detail') {
			currentRoute.children.push({
				component: 'wallee-order-detail',
				name: 'wallee.order.detail',
				isChildren: true,
				path: '/sw/order/wallee/detail/:id'
			});
		}
		next(currentRoute);
	}
});
