/* global Shopware */

import template from './sw-order.html.twig';
import './sw-order.scss';

const {Component, Context} = Shopware;
const Criteria = Shopware.Data.Criteria;

const walleeFormattedHandlerIdentifier = 'handler_walleepayment_walleepaymenthandler';

Component.override('sw-order-detail', {
	template,

	data() {
		return {
			isWalleePayment: false
		};
	},

	computed: {
		isEditable() {
			return !this.isWalleePayment || this.$route.name !== 'wallee.order.detail';
		},
		showTabs() {
			return true;
		}
	},

	watch: {
		orderId: {
			deep: true,
			handler() {
				if (!this.orderId) {
					this.setIsWalleePayment(null);
					return;
				}

				const orderRepository = this.repositoryFactory.create('order');
				const orderCriteria = new Criteria(1, 1);
				orderCriteria.addAssociation('transactions');

				orderRepository.get(this.orderId, Context.api, orderCriteria).then((order) => {
					if (order.transactions.length <= 0 ||
						!order.transactions[0].paymentMethodId
					) {
						this.setIsWalleePayment(null);
						return;
					}

					const paymentMethodId = order.transactions[0].paymentMethodId;
					if (paymentMethodId !== undefined && paymentMethodId !== null) {
						this.setIsWalleePayment(paymentMethodId);
					}
				});
			},
			immediate: true
		}
	},

	methods: {
		setIsWalleePayment(paymentMethodId) {
			if (!paymentMethodId) {
				return;
			}
			const paymentMethodRepository = this.repositoryFactory.create('payment_method');
			paymentMethodRepository.get(paymentMethodId, Context.api).then(
				(paymentMethod) => {
					this.isWalleePayment = (paymentMethod.formattedHandlerIdentifier === walleeFormattedHandlerIdentifier);
				}
			);
		}
	}
});
