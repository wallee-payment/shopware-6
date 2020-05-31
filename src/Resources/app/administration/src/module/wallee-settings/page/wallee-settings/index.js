/* global Shopware */

import template from './wallee.html.twig';
import './wallee.scss';

const {Component, Mixin} = Shopware;

Component.register('wallee-settings', {

	template: template,

	inject: [
		'WalleeConfigurationService'
	],

	mixins: [
		Mixin.getByName('notification')
	],

	data() {
		return {
			config: {},
			isLoading: false,
			isSaveSuccessful: false,

			spaceIdFilled: false,
			spaceIdErrorState: false,

			integrationFilled: false,
			integrationErrorState: false,

			userIdFilled: false,
			userIdErrorState: false,

			applicationKeyFilled: false,
			applicationKeyErrorState: false,

			isSetDefaultPaymentSuccessful: false,
			isSettingDefaultPaymentMethods: false
		};
	},

	props: {
		isLoading: {
			type: Boolean,
			required: true
		}
	},

	metaInfo() {
		return {
			title: this.$createTitle()
		};
	},

	watch: {
		config: {
			handler() {
				const defaultConfig = this.$refs.configComponent.allConfigs.null;
				const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;

				if (salesChannelId === null) {
					this.spaceIdFilled = !!this.config['WalleePayment.config.spaceId'];
					this.userIdFilled = !!this.config['WalleePayment.config.userId'];
					this.applicationKeyFilled = !!this.config['WalleePayment.config.applicationKey'];
					this.integrationFilled = !!this.config['WalleePayment.config.integration'];
				} else {
					this.spaceIdFilled = !!this.config['WalleePayment.config.spaceId']
						|| !!defaultConfig['WalleePayment.config.spaceId'];
					this.userIdFilled = !!this.config['WalleePayment.config.userId']
						|| !!defaultConfig['WalleePayment.config.userId'];
					this.applicationKeyFilled = !!this.config['WalleePayment.config.applicationKey']
						|| !!defaultConfig['WalleePayment.config.applicationKey'];
					this.integrationFilled = !!this.config['WalleePayment.config.integration']
						|| !!defaultConfig['WalleePayment.config.integration'];
				}
			},
			deep: true
		}
	},

	methods: {

		onSave() {
			if (!(this.spaceIdFilled && this.userIdFilled && this.applicationKeyFilled)) {
				this.setErrorStates();
				return;
			}
			this.save();
		},

		save() {
			this.isLoading = true;

			this.$refs.configComponent.save().then((res) => {
				if (res) {
					this.config = res;
				}
				this.registerWebHooks();
				this.synchronizePaymentMethodConfiguration();
			}).catch(() => {
				this.isLoading = false;
			});
		},

		registerWebHooks() {
			this.WalleeConfigurationService.registerWebHooks(this.$refs.configComponent.selectedSalesChannelId)
				.then((response) => {
					this.createNotificationSuccess({
						title: this.$tc('wallee-settings.settingForm.titleSuccess'),
						message: this.$tc('wallee-settings.settingForm.messageWebHookUpdated')
					});
				}).catch((errorResponse) => {
				this.createNotificationError({
					title: this.$tc('wallee-settings.settingForm.titleError'),
					message: this.$tc('wallee-settings.settingForm.messageWebHookError')
				});
				this.isLoading = false;
			});
		},

		synchronizePaymentMethodConfiguration() {
			this.WalleeConfigurationService.synchronizePaymentMethodConfiguration(this.$refs.configComponent.selectedSalesChannelId)
				.then((response) => {
					this.createNotificationSuccess({
						title: this.$tc('wallee-settings.settingForm.titleSuccess'),
						message: this.$tc('wallee-settings.settingForm.messagePaymentMethodConfigurationUpdated')
					});
					this.isLoading = false;
				}).catch((errorResponse) => {
				this.createNotificationError({
					title: this.$tc('wallee-settings.settingForm.titleError'),
					message: this.$tc('wallee-settings.settingForm.messagePaymentMethodConfigurationError')
				});
				this.isLoading = false;
			});
		},

		onSetPaymentMethodDefault() {
			this.WalleeConfigurationService.setWalleeAsSalesChannelPaymentDefault(
				this.$refs.configComponent.selectedSalesChannelId
			).then(() => {
			});
		},

		setErrorStates() {
			const messageNotBlankErrorState = {
				code: 1,
				detail: this.$tc('wallee-settings.messageNotBlank')
			};

			if (!this.spaceIdFilled) {
				this.spaceIdErrorState = messageNotBlankErrorState;
			}

			if (!this.userIdFilled) {
				this.userIdErrorState = messageNotBlankErrorState;
			}

			if (!this.applicationKeyFilled) {
				this.applicationKeyErrorState = messageNotBlankErrorState;
			}
		},
	}
});
