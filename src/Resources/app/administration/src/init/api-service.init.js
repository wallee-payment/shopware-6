/* global Shopware */

import WalleeConfigurationService from '../core/service/api/wallee-configuration.service';
import WalleeRefundService from '../core/service/api/wallee-refund.service';
import WalleeTransactionService from '../core/service/api/wallee-transaction.service';
import WalleeTransactionCompletionService
	from '../core/service/api/wallee-transaction-completion.service';
import WalleeTransactionVoidService
	from '../core/service/api/wallee-transaction-void.service';


const {Application} = Shopware;

// noinspection JSUnresolvedFunction
Application.addServiceProvider('WalleeConfigurationService', (container) => {
	const initContainer = Application.getContainer('init');
	return new WalleeConfigurationService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('WalleeRefundService', (container) => {
	const initContainer = Application.getContainer('init');
	return new WalleeRefundService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('WalleeTransactionService', (container) => {
	const initContainer = Application.getContainer('init');
	return new WalleeTransactionService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('WalleeTransactionCompletionService', (container) => {
	const initContainer = Application.getContainer('init');
	return new WalleeTransactionCompletionService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('WalleeTransactionVoidService', (container) => {
	const initContainer = Application.getContainer('init');
	return new WalleeTransactionVoidService(initContainer.httpClient, container.loginService);
});