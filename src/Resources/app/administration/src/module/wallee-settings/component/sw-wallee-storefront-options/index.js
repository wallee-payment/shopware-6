/* global Shopware */

import template from './index.html.twig';
import constants from '../../page/wallee-settings/configuration-constants'

const {Component, Mixin} = Shopware;

Component.register('sw-wallee-storefront-options', {
	template: template,

	name: 'WalleeStorefrontOptions',

	mixins: [
		Mixin.getByName('notification')
	],

	props: {
		actualConfigData: {
			type: Object,
			required: true
		},
		allConfigs: {
			type: Object,
			required: true
		},
		selectedSalesChannelId: {
			required: true
		},
		isLoading: {
			type: Boolean,
			required: true
		}
	},

	data() {
		return {
			...constants
		};
	},

	computed: {
	},

	methods: {
		checkTextFieldInheritance(value) {
			if (typeof value !== 'string') {
				return true;
			}

			return value.length <= 0;
		},

		checkNumberFieldInheritance(value) {
			if (typeof value !== 'number') {
				return true;
			}

			return value.length <= 0;
		},

		checkBoolFieldInheritance(value) {
			return typeof value !== 'boolean';
		}
	}
});
