(()=>{var I=`{% block sw_order_detail_content_tabs_general %}
    {% parent %}

{# sw-tabs-item will dissappear. See: https://github.com/shopware/shopware/blob/trunk/UPGRADE-6.7.md#sw-tabs-is-removed #}
<sw-tabs-item v-if="isWalleePayment"
			  :route="{ name: 'wallee.order.detail', params: { id: $route.params.id } }"
			  :title="$tc('wallee-order.header')">
	{{ $tc('wallee-order.header') }}
</sw-tabs-item>
{% endblock %}

{% block sw_order_detail_actions_slot_smart_bar_actions %}
<template v-if="isEditable">
	{% parent %}
</template>
{% endblock %}
`;var{Component:ie,Context:y}=Shopware,re=Shopware.Data.Criteria,oe="handler_walleepayment_walleepaymenthandler";ie.override("sw-order-detail",{template:I,data(){return{isWalleePayment:!1}},computed:{isEditable(){return!this.isWalleePayment||this.$route.name!=="wallee.order.detail"},showTabs(){return!0}},watch:{orderId:{deep:!0,handler(){if(!this.orderId){this.setIsWalleePayment(null);return}let e=this.repositoryFactory.create("order"),t=new re(1,1);t.addAssociation("transactions"),e.get(this.orderId,y.api,t).then(a=>{if(a.amountTotal<=0||a.transactions.length<=0||!a.transactions[0].paymentMethodId){this.setIsWalleePayment(null);return}let n=a.transactions[0].paymentMethodId;n!=null&&this.setIsWalleePayment(n)})},immediate:!0}},methods:{setIsWalleePayment(e){if(!e)return;this.repositoryFactory.create("payment_method").get(e,y.api).then(a=>{this.isWalleePayment=a.formattedHandlerIdentifier===oe})}}});var C=`{% block wallee_order_action_completion %}
<sw-modal variant="small"
		  :title="$tc(\`wallee-order.modal.title.capture\`)"
		  @modal-close="$emit('modal-close')">

	{% block wallee_order_action_completion_amount %}
		<mt-checkbox
				:label="$tc('wallee-order.captureAction.button.text')"
				v-model:checked="isCompletion">
        </mt-checkbox>
	{% endblock %}

	{% block wallee_order_action_completion_confirm_button %}
	<template #modal-footer>
		<mt-button variant="primary"
				   @click="completion">
			{{ $tc('wallee-order.refundAction.confirmButton.text') }}
		</mt-button>
	</template>
	{% endblock %}

	<mt-loader v-if="isLoading"></mt-loader>
</sw-modal>
{% endblock %}
`;var{Component:le,Mixin:ce,Filter:de,Utils:E}=Shopware;le.register("wallee-order-action-completion",{template:C,inject:["WalleeTransactionCompletionService"],mixins:[ce.getByName("notification")],props:{transactionData:{type:Object,required:!0}},data(){return{isLoading:!0,isCompletion:!1}},computed:{dateFilter(){return de.getByName("date")}},created(){this.createdComponent()},methods:{createdComponent(){this.isLoading=!1},completion(){this.isCompletion&&(this.isLoading=!0,this.WalleeTransactionCompletionService.createTransactionCompletion(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.captureAction.successTitle"),message:this.$tc("wallee-order.captureAction.successMessage")}),this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${E.createId()}`)})}).catch(e=>{try{this.createNotificationError({title:e.response.data.errors[0].title,message:e.response.data.errors[0].detail,autoClose:!1})}catch{this.createNotificationError({title:e.title,message:e.message,autoClose:!1})}finally{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${E.createId()}`)})}}))}}});var v=`{% block wallee_order_action_refund %}
<sw-modal variant="small"
		  :title="$tc(\`wallee-order.modal.title.refund\`)"
		  @modal-close="$emit('modal-close')">

	{% block wallee_order_action_refund_amount %}

		<mt-number-field
			:max="this.$parent.$parent.itemRefundableQuantity"
			:min="0"
			 v-model="refundQuantity"
			number-type="int"
			 :label="$tc('wallee-order.refund.refundQuantity.label')">
		</mt-number-field>

		<div>
			{{ $tc('wallee-order.refundAction.maxAvailableItemsToRefund') }}:
			<b>{{ this.$parent.$parent.itemRefundableQuantity }}</b>
		</div>
	{% endblock %}

	{% block wallee_order_action_refund_confirm_button %}
	<template #modal-footer>
		<mt-button variant="primary" @click="refund()">
			{{ $tc('wallee-order.refundAction.confirmButton.text') }}
		</mt-button>
	</template>
	{% endblock %}

	<mt-loader v-if="isLoading"></mt-loader>
</sw-modal>
{% endblock %}
`;var{Component:me,Mixin:pe,Filter:he,Utils:S}=Shopware;me.register("wallee-order-action-refund",{template:v,inject:["WalleeRefundService"],mixins:[pe.getByName("notification")],props:{transactionData:{type:Object,required:!0},orderId:{type:String,required:!0}},data(){return{refundQuantity:0,isLoading:!0,currentLineItem:""}},computed:{dateFilter(){return he.getByName("date")}},created(){this.createdComponent()},methods:{createdComponent(){this.isLoading=!1,this.refundQuantity=1},refund(){this.isLoading=!0,this.WalleeRefundService.createRefund(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id,this.refundQuantity,this.$parent.$parent.currentLineItem).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.refundAction.successTitle"),message:this.$tc("wallee-order.refundAction.successMessage")}),this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${S.createId()}`)})}).catch(e=>{try{var t=e?.response?.data?.errors?.[0]?.title??this.$tc("wallee-order.refundAction.refundCreateError.errorTitle"),a;switch(e.response.data){case"refundQuantityZero":a=this.$tc("wallee-order.refundAction.refundCreateError.messageRefundQuantityIsZero");break;case"refundExceedsQuantity":a=this.$tc("wallee-order.refundAction.refundCreateError.messageRefundQuantityExceedsAvailableBalance");break;case"methodDoesNotSupportRefund":a=this.$tc("wallee-order.refundAction.refundCreateError.messagePaymentMethodDoesNotSupportRefund");break;default:a=e.response.data.errors[0].detail}this.createNotificationError({title:t,message:a,autoClose:!1})}catch{this.createNotificationError({title:e.title,message:e.message,autoClose:!1})}finally{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${S.createId()}`)})}})}}});var A=`{% block wallee_order_action_refund_partial %}
<sw-modal variant="small"
		  :title="$tc(\`wallee-order.modal.title.refund\`)"
		  @modal-close="$emit('modal-close')">

	{% block wallee_order_action_refund_amount_partial %}
		<mt-number-field
		 :max="this.$parent.$parent.itemRefundableAmount"
		 :min="0.00"
		 v-model="refundAmount"
		 :label="$tc('wallee-order.refund.refundAmount.label')"
		 :suffix="currency">
		</mt-number-field>

		<div>
			{{ $tc('wallee-order.refundAction.maxAvailableAmountToRefund') }}:
			<b>{{ this.$parent.$parent.itemRefundableAmount }}</b>
		</div>
	{% endblock %}

	{% block wallee_order_action_refund_confirm_button_partial %}
	<template #modal-footer>
		<mt-button variant="primary" @click="createPartialRefund(this.$parent.$parent.currentLineItem)">
			{{ $tc('wallee-order.refundAction.confirmButton.text') }}
		</mt-button>
	</template>
	{% endblock %}

	<mt-loader v-if="isLoading"></mt-loader>
</sw-modal>
{% endblock %}
`;var{Component:ge,Mixin:be,Filter:_e,Utils:T}=Shopware;ge.register("wallee-order-action-refund-partial",{template:A,inject:["WalleeRefundService"],mixins:[be.getByName("notification")],props:{transactionData:{type:Object,required:!0},orderId:{type:String,required:!0}},data(){return{isLoading:!0,currency:this.transactionData.transactions[0].currency,refundAmount:0}},computed:{dateFilter(){return _e.getByName("date")}},created(){this.createdComponent()},methods:{createdComponent(){this.isLoading=!1,this.currency=this.transactionData.transactions[0].currency,this.refundAmount||(this.refundAmount=this.$parent.$parent.itemRefundableAmount)},createPartialRefund(e){this.isLoading=!0,this.WalleeRefundService.createPartialRefund(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id,this.refundAmount,e).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.refundAction.successTitle"),message:this.$tc("wallee-order.refundAction.successMessage")}),this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${T.createId()}`)})}).catch(t=>{try{var a=t?.response?.data?.errors?.[0]?.title??this.$tc("wallee-order.refundAction.refundCreateError.errorTitle"),n;t.response.data==="methodDoesNotSupportRefund"?n=this.$tc("wallee-order.refundAction.refundCreateError.messagePaymentMethodDoesNotSupportRefund"):n=t.response.data.errors[0].detail,this.createNotificationError({title:a,message:n,autoClose:!1})}catch{this.createNotificationError({title:t.title,message:t.message,autoClose:!1})}finally{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${T.createId()}`)})}})}},watch:{refundAmount(e){e!==null&&(this.refundAmount=Math.round(e*100)/100)}}});var D=`{% block wallee_order_action_refund_by_amount %}
<sw-modal variant="small"
		  :title="$tc(\`wallee-order.modal.title.refund\`)"
		  @modal-close="$emit('modal-close')">

	{% block wallee_order_action_refund_amount_by_amount %}
		<mt-number-field
		 :max="refundableAmount"
		 :min="0"
		 v-model="refundAmount"
		 :label="$tc('wallee-order.refund.refundAmount.label')"
		 :suffix="currency">
		</mt-number-field>
	{% endblock %}

	{% block wallee_order_action_refund_confirm_button_by_amount %}
	<template #modal-footer>
		<mt-button variant="primary" @click="refundByAmount()">
			{{ $tc('wallee-order.refundAction.confirmButton.text') }}
		</mt-button>
	</template>
	{% endblock %}

	<mt-loader v-if="isLoading"></mt-loader>
</sw-modal>
{% endblock %}
`;var{Component:Ie,Mixin:ye,Filter:Ce,Utils:N}=Shopware;Ie.register("wallee-order-action-refund-by-amount",{template:D,inject:["WalleeRefundService"],mixins:[ye.getByName("notification")],props:{transactionData:{type:Object,required:!0},orderId:{type:String,required:!0}},data(){return{isLoading:!0,currency:this.transactionData.transactions[0].currency,refundAmount:0,refundableAmount:0}},computed:{dateFilter(){return Ce.getByName("date")}},created(){this.createdComponent()},methods:{createdComponent(){this.isLoading=!1,this.currency=this.transactionData.transactions[0].currency,this.refundAmount=Number(this.transactionData.transactions[0].amountIncludingTax),this.refundableAmount=Number(this.transactionData.transactions[0].amountIncludingTax)},refundByAmount(){this.isLoading=!0,this.WalleeRefundService.createRefundByAmount(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id,this.refundAmount).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.refundAction.successTitle"),message:this.$tc("wallee-order.refundAction.successMessage")}),this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${N.createId()}`)})}).catch(e=>{try{var t=e?.response?.data?.errors?.[0]?.title??this.$tc("wallee-order.refundAction.refundCreateError.errorTitle"),a;switch(e.response.data){case"refundAmountZero":a=this.$tc("wallee-order.refundAction.refundCreateError.messageRefundAmountIsZero");break;case"refundExceedsAmount":a=this.$tc("wallee-order.refundAction.refundCreateError.messageRefundAmountExceedsAvailableBalance");break;case"methodDoesNotSupportRefund":a=this.$tc("wallee-order.refundAction.refundCreateError.messagePaymentMethodDoesNotSupportRefund");break;default:a=e.response.data.errors[0].detail}this.createNotificationError({title:t,message:a,autoClose:!1})}catch{this.createNotificationError({title:e.title,message:e.message,autoClose:!1})}finally{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${N.createId()}`)})}})}}});var k=`{% block wallee_order_action_void %}
<sw-modal variant="small"
		  :title="$tc(\`wallee-order.modal.title.void\`)"
		  @modal-close="$emit('modal-close')">

	{% block wallee_order_action_void_amount %}
        {# Review if this v-model:checked="isVoid" needs to change to checked #}
		<mt-checkbox
				:label="$tc('wallee-order.voidAction.confirm.message')"
				v-model:checked="isVoid">
        </mt-checkbox>
	{% endblock %}

	{% block wallee_order_action_void_confirm_button %}
	<template #modal-footer>
		<mt-button variant="primary"
				   @click="voidPayment">
			{{ $tc('wallee-order.refundAction.confirmButton.text') }}
		</mt-button>
	</template>
	{% endblock %}

	<mt-loader v-if="isLoading"></mt-loader>
</sw-modal>
{% endblock %}
`;var{Component:ve,Mixin:Se,Filter:Ae,Utils:O}=Shopware;ve.register("wallee-order-action-void",{template:k,inject:["WalleeTransactionVoidService"],mixins:[Se.getByName("notification")],props:{transactionData:{type:Object,required:!0}},data(){return{isLoading:!0,isVoid:!1}},computed:{dateFilter(){return Ae.getByName("date")},lineItemColumns(){return[{property:"uniqueId",label:this.$tc("wallee-order.refund.types.uniqueId"),rawData:!1,allowResize:!0,primary:!0,width:"auto"},{property:"name",label:this.$tc("wallee-order.refund.types.name"),rawData:!0,allowResize:!0,sortable:!0,width:"auto"},{property:"quantity",label:this.$tc("wallee-order.refund.types.quantity"),rawData:!0,allowResize:!0,width:"auto"},{property:"amountIncludingTax",label:this.$tc("wallee-order.refund.types.amountIncludingTax"),rawData:!0,allowResize:!0,inlineEdit:"string",width:"auto"},{property:"type",label:this.$tc("wallee-order.refund.types.type"),rawData:!0,allowResize:!0,sortable:!0,width:"auto"},{property:"taxAmount",label:this.$tc("wallee-order.refund.types.taxAmount"),rawData:!0,allowResize:!0,width:"auto"}]}},created(){this.createdComponent()},methods:{createdComponent(){this.isLoading=!1,this.currency=this.transactionData.transactions[0].currency,this.refundableAmount=this.transactionData.transactions[0].amountIncludingTax,this.refundAmount=this.transactionData.transactions[0].amountIncludingTax},voidPayment(){this.isVoid&&(this.isLoading=!0,this.WalleeTransactionVoidService.createTransactionVoid(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.voidAction.successTitle"),message:this.$tc("wallee-order.voidAction.successMessage")}),this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${O.createId()}`)})}).catch(e=>{try{this.createNotificationError({title:e.response.data.errors[0].title,message:e.response.data.errors[0].detail,autoClose:!1})}catch{this.createNotificationError({title:e.title,message:e.message,autoClose:!1})}finally{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${O.createId()}`)})}}))}}});var x=`{% block wallee_order_detail %}
<div class="wallee-order-detail">
	<div v-if="!isLoading">
		<mt-card :title="$tc('wallee-order.paymentDetails.cardTitle')">
			<template #grid>
				{% block wallee_order_actions_section %}
				<mt-card-section secondary slim>
					{% block wallee_order_transaction_refunds_action_button %}
						<mt-button
								variant="primary"
								size="small"
								:disabled="transaction.state != 'FULFILL' || refundableAmount <= 0"
								@click="spawnModal('refundByAmount')">
							{{ $tc('wallee-order.buttons.label.refund') }}
						</mt-button>
					{% endblock %}
					{% block wallee_order_transaction_completion_action_button %}
					<mt-button
							variant="primary"
							size="small"
							:disabled="transaction.state != 'AUTHORIZED' || isLoading"
							@click="spawnModal('completion')">
						{{ $tc('wallee-order.buttons.label.completion') }}
					</mt-button>
					{% endblock %}
					{% block wallee_order_transaction_void_action_button %}
					<mt-button
							variant="primary"
							size="small"
							:disabled="transaction.state != 'AUTHORIZED' || isLoading"
							@click="spawnModal('void')">
						{{ $tc('wallee-order.buttons.label.void') }}
					</mt-button>
					{% endblock %}
					{% block wallee_order_transaction_download_invoice_action_button %}
					<mt-button
							variant="primary"
							size="small"
							:disabled="transaction.state != 'FULFILL'"
							@click="downloadInvoice()">
						{{ $tc('wallee-order.buttons.label.download-invoice') }}
					</mt-button>
					{% endblock %}
					{% block wallee_order_transaction_download_packing_slip_action_button %}
					<mt-button
							variant="primary"
							size="small"
							:disabled="transaction.state != 'FULFILL'"
							@click="downloadPackingSlip()">
						{{ $tc('wallee-order.buttons.label.download-packing-slip') }}
					</mt-button>
					{% endblock %}
				</mt-card-section>
				{% endblock %}
			</template>
		</mt-card>
		{% block wallee_order_transaction_history_card %}
		<mt-card :title="$tc('wallee-order.transactionHistory.cardTitle')">
			<template #grid>

				{% block wallee_order_transaction_history_grid %}
				<sw-data-grid :dataSource="transactionData.transactions"
							  :columns="relatedResourceColumns"
							  :showActions="true"
							  :showSelection="false">

					<template #actions="{ item }">
						<sw-context-menu-item v-if="item.customerId">{{ $tc('wallee-order.transactionHistory.customerId') }}: {{ item.customerId }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.customerName">{{ $tc('wallee-order.transactionHistory.customerName') }}: {{ item.customerName }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.creditCardHolder">{{ $tc('wallee-order.transactionHistory.creditCardHolder') }}: {{ item.creditCardHolder }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.paymentMethodName">{{ $tc('wallee-order.transactionHistory.paymentMethod') }}: {{ item.paymentMethodName }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.brandName">{{ $tc('wallee-order.transactionHistory.paymentMethodBrand') }}: {{ item.brandName }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.pseudoCardNumber">{{ $tc('wallee-order.transactionHistory.PseudoCreditCardNumber') }}: {{ item.pseudoCardNumber }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.pseudoCardNumber && item.cardExpireMonth && item.cardExpireYear">{{ $tc('wallee-order.transactionHistory.CardExpire') }}: {{ item.cardExpireMonth }} / {{ item.cardExpireYear }}</sw-context-menu-item>
						<sw-context-menu-item v-if="item.payId">PayID: {{ item.payId }}</sw-context-menu-item>
					</template>
				</sw-data-grid>
				{% endblock %}
			</template>

		</mt-card>
		{% endblock %}
		{% block wallee_order_transaction_line_items_card %}
        <mt-card :title="$tc('wallee-order.lineItem.cardTitle')">
            <template #grid>

                {% block wallee_order_transaction_line_items_grid %}
                    <sw-data-grid
                            :dataSource="lineItems"
                            :columns="lineItemColumns"
                            :showActions="true"
                            :showSelection="true"
                            :local-mode="false"
                            :is-record-selectable="isSelectable"
                            @selection-change="onSelectionChanged"
                    >
                    {% block wallee_order_transaction_line_items_grid_grid_actions %}
                        <template #actions="{ item }">
                            <sw-context-menu-item
                                    :disabled="transaction.state != 'FULFILL' || item.refundableQuantity != item.quantity || item.refundableAmount == 0 || item.itemRefundedAmount > 0 || item.itemRefundedQuantity > 0"
                                    @click="lineItemRefund(item.uniqueId, item.quantity)">
                                {{ $tc('wallee-order.buttons.label.refund-whole-line-item') }}
                            </sw-context-menu-item>

                            <sw-context-menu-item
                                    :disabled="transaction.state != 'FULFILL' || item.refundableQuantity == 0 || item.refundableAmount == 0 || item.itemRefundedAmount > 0"
                                    @click="spawnModal('refund', item.uniqueId, item.refundableQuantity)">
                                {{ $tc('wallee-order.buttons.label.refund-line-item-by-quantity') }}
                            </sw-context-menu-item>

                            <sw-context-menu-item
                                    :disabled="transaction.state != 'FULFILL' || item.refundableQuantity == 0 || item.refundableAmount == 0 || item.itemRefundedQuantity > 0"
                                    @click="spawnModal('partialRefund', item.uniqueId, item.refundableQuantity, item.refundableAmount)">
                                {{ $tc('wallee-order.buttons.label.refund-line-item-parial') }}
                            </sw-context-menu-item>
                        </template>
                    {% endblock %}
                    {% block wallee_order_transaction_line_items_grid_bulk_actions %}
                        <template #bulk>
                            <a
                                    class="link link-danger"
                                    role="link"
                                    tabindex="0"
                                    :disabled="selectedItems.length === 0"
                                    @click="onPerformBulkAction">
                                {{ $tc('wallee-order.buttons.label.refund-line-item-selected') }}
                            </a>
                        </template>
                    {% endblock %}

                    </sw-data-grid>
                {% endblock %}
            </template>
        </mt-card>
		{% endblock %}
		{% block wallee_order_transaction_refunds_card %}
		<mt-card :title="$tc('wallee-order.refund.cardTitle')" v-if="transactionData.refunds.length > 0">
			<template #grid>

				{% block wallee_order_transaction_refunds_grid %}
				<sw-data-grid
						:dataSource="transactionData.refunds"
						:columns="refundColumns"
						:showActions="false"
						:showSelection="false">
				</sw-data-grid>
				{% endblock %}
			</template>

		</mt-card>
		{% endblock %}
		{% block wallee_order_actions_modal_refund_partial %}
			<wallee-order-action-refund-partial
					v-if="modalType === 'partialRefund'"
					:orderId="orderId"
					:transactionData="transactionData"
					:lineItems="lineItems"
					@modal-close="closeModal">
			</wallee-order-action-refund-partial>
		{% endblock %}
		{% block wallee_order_actions_modal_refund %}
		<wallee-order-action-refund
				v-if="modalType === 'refund'"
				:orderId="orderId"
				:transactionData="transactionData"
				:lineItems="lineItems"
				@modal-close="closeModal">
		</wallee-order-action-refund>
		{% endblock %}
		{% block wallee_order_actions_modal_refund_by_amount %}
			<wallee-order-action-refund-by-amount
					v-if="modalType === 'refundByAmount'"
					:orderId="orderId"
					:transactionData="transactionData"
					:lineItems="lineItems"
					@modal-close="closeModal">
			</wallee-order-action-refund-by-amount>
		{% endblock %}
		{% block wallee_order_actions_modal_completion%}
		<wallee-order-action-completion
				v-if="modalType === 'completion'"
				:orderId="orderId"
				:transactionData="transactionData"
				:lineItems="lineItems"
				@modal-close="closeModal">
		</wallee-order-action-completion>
		{% endblock %}
		{% block wallee_order_actions_modal_void %}
		<wallee-order-action-void
				v-if="modalType === 'void'"
				:orderId="orderId"
				:transactionData="transactionData"
				:lineItems="lineItems"
				@modal-close="closeModal">
		</wallee-order-action-void>
		{% endblock %}
	</div>
	<mt-loader v-if="isLoading"></mt-loader>
</div>
{% endblock %}
`;var{Component:De,Mixin:Ne,Filter:ke,Context:Oe,Utils:m}=Shopware,F=Shopware.Data.Criteria;De.register("wallee-order-detail",{template:x,inject:["WalleeTransactionService","WalleeRefundService","repositoryFactory"],mixins:[Ne.getByName("notification")],data(){return{transactionData:{transactions:[],refunds:[]},transaction:{},lineItems:[],refundableQuantity:0,itemRefundableQuantity:0,isLoading:!0,orderId:"",currency:"",modalType:"",refundAmount:0,refundableAmount:0,itemRefundedAmount:0,itemRefundedQuantity:0,itemRefundableAmount:0,currentLineItem:"",refundLineItemQuantity:[],refundLineItemAmount:[],selectedItems:[]}},metaInfo(){return{title:this.$tc("wallee-order.header")}},computed:{dateFilter(){return ke.getByName("date")},relatedResourceColumns(){return[{property:"paymentMethodName",label:this.$tc("wallee-order.transactionHistory.types.payment_method"),rawData:!0},{property:"state",label:this.$tc("wallee-order.transactionHistory.types.state"),rawData:!0},{property:"currency",label:this.$tc("wallee-order.transactionHistory.types.currency"),rawData:!0},{property:"authorized_amount",label:this.$tc("wallee-order.transactionHistory.types.authorized_amount"),rawData:!0},{property:"id",label:this.$tc("wallee-order.transactionHistory.types.transaction"),rawData:!0},{property:"customerId",label:this.$tc("wallee-order.transactionHistory.types.customer"),rawData:!0}]},lineItemColumns(){return[{property:"id",rawData:!0,visible:!1,primary:!0},{property:"uniqueId",label:this.$tc("wallee-order.lineItem.types.uniqueId"),rawData:!0,visible:!1,primary:!0},{property:"name",label:this.$tc("wallee-order.lineItem.types.name"),rawData:!0},{property:"quantity",label:this.$tc("wallee-order.lineItem.types.quantity"),rawData:!0},{property:"amountIncludingTax",label:this.$tc("wallee-order.lineItem.types.amountIncludingTax"),rawData:!0},{property:"type",label:this.$tc("wallee-order.lineItem.types.type"),rawData:!0},{property:"taxAmount",label:this.$tc("wallee-order.lineItem.types.taxAmount"),rawData:!0},{property:"refundableQuantity",rawData:!0,visible:!1}]},refundColumns(){return[{property:"id",label:this.$tc("wallee-order.refund.types.id"),rawData:!0,visible:!0,primary:!0},{property:"amount",label:this.$tc("wallee-order.refund.types.amount"),rawData:!0},{property:"state",label:this.$tc("wallee-order.refund.types.state"),rawData:!0},{property:"createdOn",label:this.$tc("wallee-order.refund.types.createdOn"),rawData:!0}]}},watch:{$route(){this.resetDataAttributes(),this.createdComponent()}},created(){this.createdComponent()},methods:{createdComponent(){this.orderId=this.$route.params.id;let e=this.repositoryFactory.create("order"),t=new F(1,1);t.addAssociation("transactions"),t.getAssociation("transactions").addSorting(F.sort("createdAt","DESC")),e.get(this.orderId,Oe.api,t).then(a=>{this.order=a,this.isLoading=!1;var n=0,i=0;let s=a.transactions[0].customFields.wallee_transaction_id;this.WalleeTransactionService.getTransactionData(a.salesChannelId,s).then(o=>{this.currency=o.transactions[0].currency,o.transactions[0].authorized_amount=m.format.currency(o.transactions[0].authorizationAmount,this.currency),o.refunds.forEach(r=>{i=parseFloat(parseFloat(i)+parseFloat(r.amount)),r.amount=m.format.currency(r.amount,this.currency),r.reductions.forEach(c=>{c.quantityReduction>0&&(this.refundLineItemQuantity[c.lineItemUniqueId]===void 0?this.refundLineItemQuantity[c.lineItemUniqueId]=c.quantityReduction:this.refundLineItemQuantity[c.lineItemUniqueId]+=c.quantityReduction),c.unitPriceReduction>0&&(this.refundLineItemAmount[c.lineItemUniqueId]===void 0?this.refundLineItemAmount[c.lineItemUniqueId]=c.unitPriceReduction:this.refundLineItemAmount[c.lineItemUniqueId]+=c.unitPriceReduction)})}),o.transactions[0].lineItems.forEach(r=>{r.id||(r.id=r.uniqueId),r.itemRefundedAmount=parseFloat(this.refundLineItemAmount[r.uniqueId]||0)*parseInt(r.quantity),r.amountIncludingTax=parseFloat(r.amountIncludingTax)||0,r.itemRefundedQuantity=parseInt(this.refundLineItemQuantity[r.uniqueId])||0,r.refundableAmount=parseFloat((r.amountIncludingTax-r.itemRefundedAmount).toFixed(2)),r.amountIncludingTax=m.format.currency(r.amountIncludingTax,this.currency),r.taxAmount=m.format.currency(r.taxAmount,this.currency),n=parseFloat(parseFloat(n)+parseFloat(r.unitPriceIncludingTax*r.quantity)),r.refundableQuantity=parseInt(parseInt(r.quantity)-parseInt(this.refundLineItemQuantity[r.uniqueId]||0))}),this.lineItems=o.transactions[0].lineItems,this.transactionData=o,this.transaction=this.transactionData.transactions[0],this.refundAmount=Number(this.transactionData.transactions[0].amountIncludingTax),this.refundableAmount=parseFloat(parseFloat(n)-parseFloat(i))}).catch(o=>{try{this.createNotificationError({title:this.$tc("wallee-order.paymentDetails.error.title"),message:o.message,autoClose:!1})}catch{this.createNotificationError({title:this.$tc("wallee-order.paymentDetails.error.title"),message:o.message,autoClose:!1})}finally{this.isLoading=!1}})})},downloadPackingSlip(){window.open(this.WalleeTransactionService.getPackingSlip(this.transaction.metaData.salesChannelId,this.transaction.id),"_blank")},downloadInvoice(){window.open(this.WalleeTransactionService.getInvoiceDocument(this.transaction.metaData.salesChannelId,this.transaction.id),"_blank")},resetDataAttributes(){this.transactionData={transactions:[],refunds:[]},this.lineItems=[],this.refundLineItemQuantity=[],this.refundLineItemAmount=[],this.isLoading=!0},spawnModal(e,t,a,n){this.modalType=e,this.currentLineItem=t,this.itemRefundableQuantity=a,this.itemRefundableAmount=isNaN(n)?0:Math.round(n*100)/100},closeModal(){this.modalType=""},lineItemRefund(e,t){this.isLoading=!0,this.WalleeRefundService.createRefund(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id,t,e).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.refundAction.successTitle"),message:this.$tc("wallee-order.refundAction.successMessage")}),this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${m.createId()}`)})}).catch(a=>{try{var n=a?.response?.data?.errors?.[0]?.title??this.$tc("wallee-order.refundAction.refundCreateError.errorTitle"),i;a.response.data==="methodDoesNotSupportRefund"?i=this.$tc("wallee-order.refundAction.refundCreateError.messagePaymentMethodDoesNotSupportRefund"):i=a.response.data.errors[0].detail,this.createNotificationError({title:n,message:i,autoClose:!1})}catch{this.createNotificationError({title:a.title,message:a.response.data,autoClose:!1})}finally{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${m.createId()}`)})}})},isSelectable(e){return e.refundableQuantity>0&&e.refundableAmount>0&&e.itemRefundedAmount==0&&e.itemRefundedQuantity==0},onSelectionChanged(e){this.selectedItems=Object.values(e)},onPerformBulkAction(){this.selectedItems.length&&(this.isLoading=!0,this.$nextTick(()=>{let e=this.selectedItems.map(t=>this.lineItemRefundBulk(t.uniqueId,t.quantity));Promise.all(e).then(()=>{this.isLoading=!1,this.$emit("modal-close"),this.$nextTick(()=>{this.$router.replace(`${this.$route.path}?hash=${m.createId()}`)})}).catch(t=>{if(t?.response?.data==="methodDoesNotSupportRefund"){this.isLoading=!1;return}this.createNotificationError({title:"Error",message:"Something went wrong with the refunds",autoClose:!1}),this.isLoading=!1})}))},lineItemRefundBulk(e,t){return new Promise((a,n)=>{this.WalleeRefundService.createRefund(this.transactionData.transactions[0].metaData.salesChannelId,this.transactionData.transactions[0].id,t,e).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-order.refundAction.successTitle"),message:this.$tc("wallee-order.refundAction.successMessage")}),a()}).catch(i=>{try{var s=i?.response?.data?.errors?.[0]?.title??this.$tc("wallee-order.refundAction.refundCreateError.errorTitle"),o;i.response.data==="methodDoesNotSupportRefund"?o=this.$tc("wallee-order.refundAction.refundCreateError.messagePaymentMethodDoesNotSupportRefund"):o=i.response.data.errors[0].detail,this.createNotificationError({title:s,message:o,autoClose:!1})}catch{this.createNotificationError({title:i.title,message:i.response.data,autoClose:!1})}finally{n(i)}})})}}});var $={"wallee-order":{buttons:{label:{completion:"Abschluss","download-invoice":"Rechnung herunterladen","download-packing-slip":"Packzettel herunterladen",refund:"Eine neue R\xFCckerstattung erstellen",void:"Genehmigung annullieren","refund-whole-line-item":"Gesamte Werbebuchung erstatten","refund-line-item-by-quantity":"R\xFCckerstattung nach Menge","refund-line-item-selected":"R\xFCckerstattung ausw\xE4hlen","refund-line-item-parial":"Teilweise R\xFCckerstattung"}},captureAction:{button:{text:"Zahlung erfassen"},currentAmount:"Betrag",isFinal:"Dies ist die endg\xFCltige Verbuchung",maxAmount:"Maximaler Betrag",successMessage:"Ihre Verbuchung war erfolgreich",successTitle:"Erfolg"},general:{title:"Bestellungen"},header:"Wallee Payment",lineItem:{cardTitle:"Einzelposten",types:{amountIncludingTax:"Betrag",name:"Name",quantity:"Anzahl",taxAmount:"Steuern",type:"Typ",uniqueId:"Eindeutige ID"}},modal:{title:{capture:"Erfassen",refund:"Neue Gutschrift",void:"Autorisierung aufheben"}},paymentDetails:{cardTitle:"Zahlung",error:{title:"Fehler beim Abrufen von Zahlungsdetails von Wallee"}},refund:{cardTitle:"Gutschriften",refundAmount:{label:"Gutschriftsbetrag"},refundQuantity:{label:"Refund Menge"},types:{amount:"Betrag",createdOn:"Erstellt am",id:"ID",state:"Staat"}},refundAction:{confirmButton:{text:"Ausf\xFChren"},refundAmount:{label:"Betrag",placeholder:"Einen Betrag eingeben"},successMessage:"Ihre R\xFCckerstattung war erfolgreich",successTitle:"Erfolg",maxAvailableItemsToRefund:"Maximal Verf\xFCgbare Artikel zum Erstatten",maxAvailableAmountToRefund:"Maximal verf\xFCgbarer Erstattungsbetrag",refundCreateError:{errorTitle:"Fehler beim Erstellen der R\xFCckerstattung.",messageRefundAmountExceedsAvailableBalance:"Der R\xFCckerstattungsbetrag \xFCbersteigt das verf\xFCgbare Guthaben.",messageRefundAmountIsZero:"Der R\xFCckerstattungsbetrag muss gr\xF6\xDFer als 0 sein.",messageRefundQuantityExceedsAvailableBalance:"R\xFCckerstattung nach Menge \xFCberschreitet die maximal verf\xFCgbare Anzahl an Artikeln zur R\xFCckerstattung.",messageRefundQuantityIsZero:"R\xFCckerstattung nach Menge muss gr\xF6\xDFer als 0 sein.",messagePaymentMethodDoesNotSupportRefund:"Die Zahlungsmethode unterst\xFCtzt keine Online-R\xFCckerstattungen."}},transactionHistory:{cardTitle:"Einzelheiten",types:{authorized_amount:"Autorisierter Betrag",currency:"W\xE4hrung",customer:"Kunde",payment_method:"Zahlungsweise",state:"Staat",transaction:"Transaktion"},customerId:"Customer ID",customerName:"Customer Name",creditCardHolder:"Kreditkarteninhaber",paymentMethod:"Zahlungsart",paymentMethodBrand:"Marke der Zahlungsmethode",PseudoCreditCardNumber:"Pseudo-Kreditkartennummer",CardExpire:"Karte verf\xE4llt"},voidAction:{confirm:{button:{cancel:"Nein",confirm:"Autorisierung aufheben"},message:"Wollen Sie diese Zahlung wirklich stornieren?"},successMessage:"Die Zahlung wurde erfolgreich annulliert",successTitle:"Erfolg"}}};var P={"wallee-order":{buttons:{label:{completion:"Complete","download-invoice":"Download Invoice","download-packing-slip":"Download Packing Slip",refund:"Create a new refund",void:"Cancel authorization","refund-whole-line-item":"Refund whole line item","refund-line-item-by-quantity":"Refund by quantity","refund-line-item-selected":"Refund selected","refund-line-item-parial":"Partial refund"}},captureAction:{button:{text:"Capture payment"},currentAmount:"Amount",isFinal:"This is final capture",maxAmount:"Maximum amount",successMessage:"Your capture was successful.",successTitle:"Success"},general:{title:"Orders"},header:"Wallee Payment",lineItem:{cardTitle:"Line Items",types:{amountIncludingTax:"Amount",name:"Name",quantity:"Quantity",taxAmount:"Taxes",type:"Type",uniqueId:"Unique ID"}},modal:{title:{capture:"Capture",refund:"New refund",void:"Cancel authorization"}},paymentDetails:{cardTitle:"Payment",error:{title:"Error fetching payment details from Wallee"}},refund:{cardTitle:"Refunds",refundAmount:{label:"Refund Amount"},refundQuantity:{label:"Refund Quantity"},types:{amount:"Amount",createdOn:"Created On",id:"ID",state:"State"}},refundAction:{confirmButton:{text:"Execute"},refundAmount:{label:"Amount",placeholder:"Enter a amount"},successMessage:"Your refund was successful.",successTitle:"Success",maxAvailableItemsToRefund:"Maximum available items to refund",maxAvailableAmountToRefund:"Maximum available amount to refund",refundCreateError:{errorTitle:"Error while creating the refund.",messageRefundAmountExceedsAvailableBalance:"Refund amount exceeds available balance.",messageRefundAmountIsZero:"Refund amount must be greater than 0.",messageRefundQuantityExceedsAvailableBalance:"Refund by quantity exceeds maximum available items to refund.",messageRefundQuantityIsZero:"Refund by quantity must be greater than 0.",messagePaymentMethodDoesNotSupportRefund:"Payment method does not support online refunds."}},transactionHistory:{cardTitle:"Details",types:{authorized_amount:"Authorized Amount",currency:"Currency",customer:"Customer",payment_method:"Payment Method",state:"State",transaction:"Transaction"},customerId:"Customer ID",customerName:"Customer Name",creditCardHolder:"Credit Card Holder",paymentMethod:"Payment Method",paymentMethodBrand:"Payment Method Brand",PseudoCreditCardNumber:"Pseudo Credit Card Number",CardExpire:"Card Expire"},voidAction:{confirm:{button:{cancel:"No",confirm:"Cancel authorization"},message:"Do you really want to cancel this payment?"},successMessage:"The payment was successfully voided.",successTitle:"Success"}}};var L={"wallee-order":{buttons:{label:{completion:"Termin\xE9e","download-invoice":"T\xE9l\xE9charger la facture","download-packing-slip":"T\xE9l\xE9charger le bordereau d'exp\xE9dition",refund:"Cr\xE9er un nouveau remboursement",void:"Annulez l'autorisation","refund-whole-line-item":"Remboursement de la ligne enti\xE8re","refund-line-item-by-quantity":"Remboursement par quantit\xE9","refund-line-item-selected":"Rembourser s\xE9lectionn\xE9s","refund-line-item-parial":"Remboursement partiel"}},captureAction:{button:{text:"Capture du paiement"},currentAmount:"Montant",isFinal:"C'est la capture finale",maxAmount:"Montant maximal",successMessage:"Votre capture a \xE9t\xE9 r\xE9ussie.",successTitle:"Succ\xE8s"},general:{title:"Commandes"},header:"Wallee Paiement",lineItem:{cardTitle:"Articles de ligne",types:{amountIncludingTax:"Montant",name:"Nom",quantity:"Quantit\xE9",taxAmount:"Taxes",type:"Type",uniqueId:"ID unique"}},modal:{title:{capture:"Capture",refund:"Nouveau remboursement",void:"Annulez l'autorisation"}},paymentDetails:{cardTitle:"Paiement",error:{title:"Erreur dans la r\xE9cup\xE9ration des d\xE9tails du paiement \xE0 partir de Wallee"}},refund:{cardTitle:"Remboursements",refundAmount:{label:"Montant du remboursement"},refundQuantity:{label:"Quantit\xE9 \xE0 rembourser"},types:{amount:"Montant",createdOn:"Cr\xE9\xE9 le",id:"ID",state:"\xC9tat"}},refundAction:{confirmButton:{text:"Ex\xE9cutez"},refundAmount:{label:"Montant",placeholder:"Entrez un montant"},successMessage:"Votre remboursement a \xE9t\xE9 effectu\xE9 avec succ\xE8s.",successTitle:"Succ\xE8s",maxAvailableItemsToRefund:"Nombre maximum d'articles disponibles pour le remboursement",maxAvailableAmountToRefund:"Montant maximal disponible pour le remboursement",refundCreateError:{errorTitle:"Erreur lors de la cr\xE9ation du remboursement.",messageRefundAmountExceedsAvailableBalance:"Le montant du remboursement d\xE9passe le solde disponible.",messageRefundAmountIsZero:"Le montant du remboursement doit \xEAtre sup\xE9rieur \xE0 0.",messageRefundQuantityExceedsAvailableBalance:"Le remboursement par quantit\xE9 d\xE9passe le nombre maximal d\u2019articles remboursables.",messageRefundQuantityIsZero:"Le remboursement par quantit\xE9 doit \xEAtre sup\xE9rieur \xE0 0.",messagePaymentMethodDoesNotSupportRefund:"Le mode de paiement ne prend pas en charge les remboursements en ligne."}},transactionHistory:{cardTitle:"D\xE9tails",types:{authorized_amount:"Montant autoris\xE9",currency:"Monnaie",customer:"Client",payment_method:"Mode de paiement",state:"\xC9tat",transaction:"Transaction"},customerId:"Customer ID",customerName:"Customer Name",creditCardHolder:"Titulaire de la carte de cr\xE9dit",paymentMethod:"Mode de paiement",paymentMethodBrand:"Marque du mode de paiement",PseudoCreditCardNumber:"Pseudo num\xE9ro de carte de cr\xE9dit",CardExpire:"La carte expire"},voidAction:{confirm:{button:{cancel:"Non",confirm:"Annulez l'autorisation"},message:"Voulez-vous vraiment annuler ce paiement?"},successMessage:"Le paiement a \xE9t\xE9 annul\xE9 avec succ\xE8s.",successTitle:"Succ\xE8s"}}};var R={"wallee-order":{buttons:{label:{completion:"Completato","download-invoice":"Scarica fattura","download-packing-slip":"Scarica distinta di imballaggio",refund:"Crea un nuovo rimborso",void:"Annulla autorizzazione","refund-whole-line-item":"Rimborso intera riga","refund-line-item-by-quantity":"Rimborso per quantit\xE0","refund-line-item-selected":"Rimborso selezionati","refund-line-item-parial":"Rimborso parziale"}},captureAction:{button:{text:"Cattura pagamento"},currentAmount:"Importo",isFinal:"Questa \xE8 la cattura finale",maxAmount:"Importo massimo",successMessage:"La tua cattura ha avuto successo.",successTitle:"Successo"},general:{title:"Ordini"},header:"Pagamento Wallee",lineItem:{cardTitle:"Articoli di linea",types:{amountIncludingTax:"Importo",name:"Nome",quantity:"Quantit\xE0",taxAmount:"Tasse",type:"Tipo",uniqueId:"ID unico"}},modal:{title:{capture:"Cattura",refund:"Nuovo rimborso",void:"Annulla autorizzazione"}},paymentDetails:{cardTitle:"Pagamento",error:{title:"Errore nel recupero dei dettagli del pagamento da Wallee"}},refund:{cardTitle:"Rimborsi",refundAmount:{label:"Importo del rimborso"},refundQuantity:{label:"Quantit\xE0 di rimborso"},types:{amount:"Importo",createdOn:"Creato il",id:"ID",state:"Stato"}},refundAction:{confirmButton:{text:"Esegui"},refundAmount:{label:"Importo",placeholder:"Inserisci un importo"},successMessage:"Il tuo rimborso \xE8 andato a buon fine.",successTitle:"Successo",maxAvailableItemsToRefund:"Numero massimo di articoli disponibili da rimborsare",maxAvailableAmountToRefund:"Importo massimo disponibile per il rimborso",refundCreateError:{errorTitle:"Errore durante la creazione del rimborso.",messageRefundAmountExceedsAvailableBalance:"LL'importo del rimborso supera il saldo disponibile.",messageRefundAmountIsZero:"L'importo del rimborso deve essere superiore a 0.",messageRefundQuantityExceedsAvailableBalance:"Il rimborso per quantit\xE0 supera il numero massimo di articoli rimborsabili.",messageRefundQuantityIsZero:"Il rimborso per quantit\xE0 deve essere maggiore di 0.",messagePaymentMethodDoesNotSupportRefund:"Il metodo di pagamento non supporta i rimborsi online."}},transactionHistory:{cardTitle:"Dettagli",types:{authorized_amount:"Importo autorizzato",currency:"Valuta",customer:"Cliente",payment_method:"Metodo di pagamento",state:"Stato",transaction:"Transazione"},customerId:"Customer ID",customerName:"Customer Name",creditCardHolder:"Proprietario della carta di credito",paymentMethod:"Metodo di pagamento",paymentMethodBrand:"Metodo di pagamento Marca",PseudoCreditCardNumber:"Numero di carta di credito pseudo",CardExpire:"La carta scade"},voidAction:{confirm:{button:{cancel:"No",confirm:"Annulla autorizzazione"},message:"Vuoi davvero annullare questo pagamento?"},successMessage:"Il pagamento \xE8 stato annullato con successo.",successTitle:"Successo"}}};var{Module:Le}=Shopware;Le.register("wallee-order",{type:"plugin",name:"Wallee",title:"wallee-order.general.title",description:"wallee-order.general.descriptionTextModule",version:"1.0.1",targetVersion:"1.0.1",color:"#2b52ff",snippets:{"de-DE":$,"en-GB":P,"fr-FR":L,"it-IT":R},routeMiddleware(e,t){t.name==="sw.order.detail"&&t.children.push({component:"wallee-order-detail",name:"wallee.order.detail",isChildren:!0,path:"/sw/order/wallee/detail/:id"}),e(t)}});Shopware.Service("privileges").addPrivilegeMappingEntry({category:"permissions",parent:"wallee",key:"wallee",roles:{viewer:{privileges:["sales_channel:read","sales_channel_payment_method:read","system_config:read"],dependencies:[]},editor:{privileges:["sales_channel:update","sales_channel_payment_method:create","sales_channel_payment_method:update","system_config:update","system_config:create","system_config:delete"],dependencies:["wallee.viewer"]}}});Shopware.Service("privileges").addPrivilegeMappingEntry({category:"permissions",parent:null,key:"sales_channel",roles:{viewer:{privileges:["sales_channel_payment_method:read"]},editor:{privileges:["payment_method:update"]},creator:{privileges:["payment_method:create","shipping_method:create","delivery_time:create"]},deleter:{privileges:["payment_method:delete"]}}});var W=`{% block wallee_settings %}
    <sw-page class="wallee-settings">

        {% block wallee_settings_header %}
            <template #smart-bar-header>
                <h2>
                    {{ $tc('sw-settings.index.title') }}
                    <mt-icon name="small-arrow-medium-right" size="16px"></mt-icon>
                    {{ $tc('wallee-settings.header') }}
                </h2>
            </template>
        {% endblock %}

        {% block wallee_settings_actions %}
            <template #smart-bar-actions>
                {% block wallee_settings_actions_save %}
                    <mt-button
                            v-model:value="isSaveSuccessful"
                            class="sw-settings-login-registration__save-action"
                            variant="primary"
                            :isLoading="isLoading"
                            :disabled="isLoading"
                            @click="onSave">
                        {{ $tc('wallee-settings.settingForm.save') }}
                    </mt-button>
                {% endblock %}
            </template>
        {% endblock %}

        {% block wallee_settings_content %}
            <template #content>

                {% block wallee_settings_content_card %}
                    <mt-card-view>

                        {% block wallee_settings_content_card_channel_config %}
                            <sw-sales-channel-config v-model:value="config"
                                                        v-model:selectedSalesChannelId="selectedSalesChannelId"
                                                        ref="configComponent"
                                                        :domain="CONFIG_DOMAIN">

                                {% block wallee_settings_content_card_channel_config_sales_channel %}
                                    <template #select="{ onInput, selectedSalesChannelId, salesChannel }">

                                        {% block wallee_settings_content_card_channel_config_sales_channel_card %}
                                            <mt-card title="Sales Channel Switch">

                                                {% block wallee_settings_content_card_channel_config_sales_channel_card_title %}
                                                <sw-sales-channel-switch
                                                                ref="channelSwitch"
                                                                @change-sales-channel-id="onSalesChannelSwitchChange($event, onInput)">
                                                </sw-sales-channel-switch>
                                                {% endblock %}
                                                {% block wallee_settings_content_card_channel_config_sales_channel_card_footer %}
                                                    <template #footer>

                                                        {% block wallee_settings_content_card_channel_config_sales_channel_card_footer_container %}
                                                            <sw-container columns="2fr 1fr" gap="0px 30px">

                                                                {% block wallee_settings_content_card_channel_config_sales_channel_card_footer_container_text %}
                                                                    <p>{{ $tc('wallee-settings.salesChannelCard.button.description') }}</p>
                                                                {% endblock %}

                                                                {% block wallee_settings_content_card_channel_config_sales_channel_card_footer_container_button %}
                                                                    <sw-button
                                                                            variant="primary"
                                                                            v-model:value="isSetDefaultPaymentSuccessful"
                                                                            :isLoading="isSettingDefaultPaymentMethods"
                                                                            @click="onSetPaymentMethodDefault">
                                                                        {{ $tc('wallee-settings.salesChannelCard.button.label') }}
                                                                    </sw-button>
                                                                {% endblock %}
                                                            </sw-container>
                                                        {% endblock %}
                                                    </template>
                                                {% endblock %}
                                            </mt-card>
                                        {% endblock %}
                                    </template>
                                {% endblock %}

                                {% block wallee_settings_content_card_channel_config_cards %}
                                    <template #content="{ actualConfigData, allConfigs, selectedSalesChannelId }">
                                        <div v-if="actualConfigData">

                                            <sw-wallee-credentials
                                                    :actualConfigData="actualConfigData"
                                                    :allConfigs="allConfigs"
                                                    :selectedSalesChannelId="selectedSalesChannelId"
                                                    :spaceIdErrorState="spaceIdErrorState"
                                                    :userIdErrorState="userIdErrorState"
                                                    :applicationKeyErrorState="applicationKeyErrorState"
                                                    :spaceIdFilled="spaceIdFilled"
                                                    :userIdFilled="userIdFilled"
                                                    :applicationKeyFilled="applicationKeyFilled"
                                                    :isLoading="isLoading"
                                                    :isTesting="isTesting"
                                                    @check-api-connection-event="onCheckApiConnection"
                                            ></sw-wallee-credentials>

                                            <sw-wallee-options
                                                    :actualConfigData="actualConfigData"
                                                    :allConfigs="allConfigs"
                                                    :isLoading="isLoading"
                                                    :selectedSalesChannelId="selectedSalesChannelId"
                                            >
                                            </sw-wallee-options>

                                            <sw-wallee-storefront-options
                                                    :actualConfigData="actualConfigData"
                                                    :allConfigs="allConfigs"
                                                    :isLoading="isLoading"
                                                    :selectedSalesChannelId="selectedSalesChannelId"
                                            >
                                            </sw-wallee-storefront-options>

                                            <sw-wallee-advanced-options
                                                    :actualConfigData="actualConfigData"
                                                    :allConfigs="allConfigs"
                                                    :isLoading="isLoading"
                                                    :selectedSalesChannelId="selectedSalesChannelId"
                                            >
                                            </sw-wallee-advanced-options>


                                        </div>
                                    </template>
                                {% endblock %}

                            </sw-sales-channel-config>
                        {% endblock %}

                        {% block wallee_settings_content_card_loading %}
                            <mt-loader v-if="isLoading"></mt-loader>
                        {% endblock %}
                    </mt-card-view>
                {% endblock %}

            </template>
        {% endblock %}
    </sw-page>
{% endblock %}
`;var d="WalleePayment.config",We=d+".applicationKey",Me=d+".emailEnabled",Be=d+".integration",Ge=d+".lineItemConsistencyEnabled",Ve=d+".spaceId",qe=d+".spaceViewId",ze=d+".storefrontInvoiceDownloadEnabled",Ue=d+".userId",He=d+".storefrontWebhooksUpdateEnabled",Ke=d+".storefrontPaymentsUpdateEnabled",Qe=d+".keepFailedPaymentsOrderOpen",Ye="8a243080f92e4c719546314b577cf82b",l={CONFIG_DOMAIN:d,CONFIG_APPLICATION_KEY:We,CONFIG_EMAIL_ENABLED:Me,CONFIG_INTEGRATION:Be,CONFIG_LINE_ITEM_CONSISTENCY_ENABLED:Ge,CONFIG_SPACE_ID:Ve,CONFIG_SPACE_VIEW_ID:qe,CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED:ze,CONFIG_USER_ID:Ue,CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED:He,CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED:Ke,CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN:Qe,STOREFRONT_SALES_CHANNEL_TYPE_ID:Ye};var{Component:Ze,Mixin:M}=Shopware;Ze.register("wallee-settings",{template:W,inject:["acl","WalleeConfigurationService","repositoryFactory"],mixins:[M.getByName("notification"),M.getByName("sw-inline-snippet")],data(){return{config:{},isLoading:!1,isTesting:!1,isSaveSuccessful:!1,applicationKeyFilled:!1,applicationKeyErrorState:!1,spaceIdFilled:!1,spaceIdErrorState:!1,userIdFilled:!1,userIdErrorState:!1,isSetDefaultPaymentSuccessful:!1,isSettingDefaultPaymentMethods:!1,selectedSalesChannelId:null,configIntegrationDefaultValue:"payment_page",configEmailEnabledDefaultValue:!0,configLineItemConsistencyEnabledDefaultValue:!0,configStorefrontInvoiceDownloadEnabledEnabledDefaultValue:!0,configStorefrontWebhooksUpdateEnabledDefaultValue:!0,configStorefrontPaymentsUpdateEnabledDefaultValue:!0,configKeepFailedPaymentsOrderOpenDefaultValue:!1,...l}},props:{isLoading:{type:Boolean,required:!0}},metaInfo(){return{title:this.$createTitle()}},watch:{config:{handler(e){let t=(this.$refs.configComponent.allConfigs||{}).null||{};this.selectedSalesChannelId===null?(this.applicationKeyFilled=!!this.config[this.CONFIG_APPLICATION_KEY],this.spaceIdFilled=!!this.config[this.CONFIG_SPACE_ID],this.userIdFilled=!!this.config[this.CONFIG_USER_ID],this.CONFIG_INTEGRATION in this.config||(this.config[this.CONFIG_INTEGRATION]=this.configIntegrationDefaultValue),this.CONFIG_EMAIL_ENABLED in this.config||(this.config[this.CONFIG_EMAIL_ENABLED]=this.configEmailEnabledDefaultValue),this.CONFIG_LINE_ITEM_CONSISTENCY_ENABLED in this.config||(this.config[this.CONFIG_LINE_ITEM_CONSISTENCY_ENABLED]=this.configLineItemConsistencyEnabledDefaultValue),this.CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED in this.config||(this.config[this.CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED]=this.configStorefrontInvoiceDownloadEnabledEnabledDefaultValue),this.CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED in this.config||(this.config[this.CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED]=this.configStorefrontWebhooksUpdateEnabledDefaultValue),this.CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED in this.config||(this.config[this.CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED]=this.configStorefrontPaymentsUpdateEnabledDefaultValue),this.CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN in this.config||(this.config[this.CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN]=this.configKeepFailedPaymentsOrderOpenDefaultValue)):(this.applicationKeyFilled=!!this.config[this.CONFIG_APPLICATION_KEY]||!!t[this.CONFIG_APPLICATION_KEY],this.spaceIdFilled=!!this.config[this.CONFIG_SPACE_ID]||!!t[this.CONFIG_SPACE_ID],this.userIdFilled=!!this.config[this.CONFIG_USER_ID]||!!t[this.CONFIG_USER_ID],(!(this.CONFIG_INTEGRATION in this.config)||!(this.CONFIG_INTEGRATION in t))&&(this.config[this.CONFIG_INTEGRATION]=this.configIntegrationDefaultValue),(!(this.CONFIG_EMAIL_ENABLED in this.config)||!(this.CONFIG_EMAIL_ENABLED in t))&&(this.config[this.CONFIG_EMAIL_ENABLED]=this.configEmailEnabledDefaultValue),(!(this.CONFIG_LINE_ITEM_CONSISTENCY_ENABLED in this.config)||!(this.CONFIG_LINE_ITEM_CONSISTENCY_ENABLED in t))&&(this.config[this.CONFIG_LINE_ITEM_CONSISTENCY_ENABLED]=this.configLineItemConsistencyEnabledDefaultValue),(!(this.CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED in this.config)||!(this.CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED in t))&&(this.config[this.CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED]=this.configStorefrontInvoiceDownloadEnabledEnabledDefaultValue),(!(this.CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED in this.config)||!(this.CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED in t))&&(this.config[this.CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED]=this.configStorefrontWebhooksUpdateEnabledDefaultValue),(!(this.CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED in this.config)||!(this.CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED in t))&&(this.config[this.CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED]=this.configStorefrontPaymentsUpdateEnabledDefaultValue),(!(this.CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN in this.config)||!(this.CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN in t))&&(this.config[this.CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN]=this.configKeepFailedPaymentsOrderOpenDefaultValue)),this.$emit("salesChannelChanged"),this.$emit("update:value",e)},deep:!0},selectedSalesChannelId:{handler(e){this.$nextTick(()=>{this.$refs.channelSwitch&&(this.$refs.channelSwitch.salesChannelId=e||"")})}}},methods:{checkTextFieldInheritance(e){return typeof e!="string"?!0:e.length<=0},checkNumberFieldInheritance(e){return typeof e!="number"?!0:e.length<=0},checkBoolFieldInheritance(e){return typeof e!="boolean"},getInheritValue(e){return this.selectedSalesChannelId==null?this.actualConfigData[e]:this.allConfigs.null[e]},async onSave(){if(!(this.spaceIdFilled&&this.userIdFilled&&this.applicationKeyFilled)){this.setErrorStates();return}this.isLoading=!0;let e=await this.validateHeadlessIntegration();if(e==="HEADLESS"){this.createNotificationError({title:this.$tc("wallee-settings.settingForm.titleError"),message:this.$tc("wallee-settings.settingForm.messageHeadlessIntegrationError")}),this.isLoading=!1;return}else if(e==="GLOBAL"){this.createNotificationError({title:this.$tc("wallee-settings.settingForm.titleError"),message:this.$tc("wallee-settings.settingForm.messageGlobalIframeError")}),this.isLoading=!1;return}this.save()},async validateHeadlessIntegration(){let e=this.selectedSalesChannelId;if(this.config[this.CONFIG_INTEGRATION]==="payment_page")return null;let a=this.repositoryFactory.create("sales_channel");try{if(e){if(!((await a.get(e,Shopware.Context.api)).typeId.replace(/-/g,"")===l.STOREFRONT_SALES_CHANNEL_TYPE_ID))return"HEADLESS"}else{let n=new Shopware.Data.Criteria;if(n.addFilter(Shopware.Data.Criteria.not("AND",[Shopware.Data.Criteria.equals("typeId",l.STOREFRONT_SALES_CHANNEL_TYPE_ID)])),n.setLimit(1),(await a.search(n,Shopware.Context.api)).total>0)return"GLOBAL"}return null}catch(n){return console.error(n),null}},save(){this.isLoading=!0,this.$refs.configComponent.save().then(e=>{e&&(this.config=e),this.registerWebHooks(),this.synchronizePaymentMethodConfiguration(),this.installOrderDeliveryStates()}).catch(e=>{console.error("Error:",e),this.isLoading=!1})},registerWebHooks(){if(this.config[this.CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED]===!1)return!1;this.WalleeConfigurationService.registerWebHooks(this.selectedSalesChannelId).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-settings.settingForm.titleSuccess"),message:this.$tc("wallee-settings.settingForm.messageWebHookUpdated")})}).catch(e=>{this.createNotificationError({title:this.$tc("wallee-settings.settingForm.titleError"),message:this.$tc("wallee-settings.settingForm.messageWebHookError")}),this.isLoading=!1,console.error("Error:",e)})},synchronizePaymentMethodConfiguration(){if(this.config[this.CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED]===!1)return!1;this.WalleeConfigurationService.synchronizePaymentMethodConfiguration(this.selectedSalesChannelId).then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-settings.settingForm.titleSuccess"),message:this.$tc("wallee-settings.settingForm.messagePaymentMethodConfigurationUpdated")}),this.isLoading=!1}).catch(e=>{this.createNotificationError({title:this.$tc("wallee-settings.settingForm.titleError"),message:this.$tc("wallee-settings.settingForm.messagePaymentMethodConfigurationError")}),this.isLoading=!1,console.error("Error:",e)})},installOrderDeliveryStates(){this.WalleeConfigurationService.installOrderDeliveryStates().then(()=>{this.createNotificationSuccess({title:this.$tc("wallee-settings.settingForm.titleSuccess"),message:this.$tc("wallee-settings.settingForm.messageOrderDeliveryStateUpdated")}),this.isLoading=!1}).catch(()=>{this.createNotificationError({title:this.$tc("wallee-settings.settingForm.titleError"),message:this.$tc("wallee-settings.settingForm.messageOrderDeliveryStateError")}),this.isLoading=!1})},onSetPaymentMethodDefault(){this.isSettingDefaultPaymentMethods=!0,this.WalleeConfigurationService.setWalleeAsSalesChannelPaymentDefault(this.selectedSalesChannelId).then(()=>{this.isSettingDefaultPaymentMethods=!1,this.isSetDefaultPaymentSuccessful=!0,this.createNotificationSuccess({title:this.$tc("wallee-settings.settingForm.titleSuccess"),message:this.$tc("wallee-settings.salesChannelCard.messageDefaultPaymentUpdated")})})},setErrorStates(){let e={code:1,detail:this.$tc("wallee-settings.messageNotBlank")};this.spaceIdFilled||(this.spaceIdErrorState=e),this.userIdFilled||(this.userIdErrorState=e),this.applicationKeyFilled||(this.applicationKeyErrorState=e)},onCheckApiConnection(e){let{spaceId:t,userId:a,applicationKey:n}=e;this.isTesting=!0,this.WalleeConfigurationService.checkApiConnection(t,a,n).then(i=>{i.result===200?this.createNotificationSuccess({title:this.$tc("wallee-settings.settingForm.credentials.alert.title"),message:this.$tc("wallee-settings.settingForm.credentials.alert.successMessage")}):this.createNotificationError({title:this.$tc("wallee-settings.settingForm.credentials.alert.title"),message:this.$tc("wallee-settings.settingForm.credentials.alert.errorMessage")}),this.isTesting=!1}).catch(()=>{this.createNotificationError({title:this.$tc("wallee-settings.settingForm.credentials.alert.title"),message:this.$tc("wallee-settings.settingForm.credentials.alert.errorMessage")}),this.isTesting=!1})},onSalesChannelSwitchChange(e,t){this.selectedSalesChannelId=e,typeof t=="function"&&t(e)}}});var B=`{% block wallee_settings_content_card_channel_config_credentials %}
	<mt-card
			class="mt-card"
			:title="$tc('wallee-settings.settingForm.credentials.cardTitle')"
			v-if="actualConfigData"
	>

		{% block wallee_settings_content_card_channel_config_credentials_card_container %}
			<sw-container>

				{% block wallee_settings_content_card_channel_config_credentials_card_container_settings %}
					<div v-if="actualConfigData" class="wallee-settings-credentials-fields">

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_space_id %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_SPACE_ID]"
									:inheritedValue="getInheritedValue(CONFIG_SPACE_ID)"
									@update:value="onSwitchInput">
								<template #content="props">
									<mt-number-field
											:name="CONFIG_SPACE_ID"
											:required="true"
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.credentials.spaceId.label')"
											:helpText="$tc('wallee-settings.settingForm.credentials.spaceId.tooltipText')"
											:disabled="!acl.can('wallee.editor')"
											:model-value="props.currentValue"
											:error="spaceIdErrorState"
											@update:model-value="props.updateCurrentValue">
									</mt-number-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_user_id %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_USER_ID]"
									:inheritedValue="getInheritedValue(CONFIG_USER_ID)"
									:customInheritationCheckFunction="checkNumberFieldInheritance">
								<template #content="props">
									<mt-number-field
											:name="CONFIG_USER_ID"
											:required="true"
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.credentials.userId.label')"
											:helpText="$tc('wallee-settings.settingForm.credentials.userId.tooltipText')"
											:disabled="!acl.can('wallee.editor')"
											:model-value="props.currentValue"
											:error="userIdErrorState"
											@update:model-value="props.updateCurrentValue">
									</mt-number-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_application_key %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_APPLICATION_KEY]"
									:inheritedValue="getInheritedValue(CONFIG_APPLICATION_KEY)"
									:customInheritationCheckFunction="checkTextFieldInheritance">
								<template #content="props">
									<mt-password-field
											:name="CONFIG_APPLICATION_KEY"
											:required="true"
											:passwordToggleAble="true"
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.credentials.applicationKey.label')"
											:helpText="$tc('wallee-settings.settingForm.credentials.applicationKey.tooltipText')"
											:disabled="!acl.can('wallee.editor')"
											:model-value="props.currentValue"
											:error="applicationKeyErrorState"
											@update:model-value="props.updateCurrentValue">
									</mt-password-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}
					</div>
				{% endblock %}

				{% verbatim %}
				<sw-container columns="1fr 1fr" gap="0px 30px">
					<mt-button
							variant="primary"
							:isLoading="isTesting"
							@click="emitCheckApiConnectionEvent">
						{{ $tc('wallee-settings.settingForm.credentials.button.label') }}
					</mt-button>
				</sw-container>
				{% endverbatim %}

			</sw-container>
		{% endblock %}
	</mt-card>

{% endblock %}
`;var{Component:Je,Mixin:Xe}=Shopware;Je.register("sw-wallee-credentials",{template:B,name:"WalleeCredentials",inject:["acl"],mixins:[Xe.getByName("notification")],props:{actualConfigData:{type:Object,required:!0},allConfigs:{type:Object,required:!0},selectedSalesChannelId:{type:[String,null],required:!1,default:null},spaceIdFilled:{type:Boolean,required:!0},spaceIdErrorState:{required:!0},userIdFilled:{type:Boolean,required:!0},userIdErrorState:{required:!0},applicationKeyFilled:{type:Boolean,required:!0},applicationKeyErrorState:{required:!0},isLoading:{type:Boolean,required:!0},isTesting:{type:Boolean,required:!1}},data(){return{...l}},computed:{currentConfig(){return this.selectedSalesChannelId&&this.allConfigs[this.selectedSalesChannelId]?this.allConfigs[this.selectedSalesChannelId]:this.allConfigs.null||{}}},methods:{checkTextFieldInheritance(e){return!e||e.length<=0},checkNumberFieldInheritance(e){return e==null||e===""},checkBoolFieldInheritance(e){return typeof e!="boolean"},emitCheckApiConnectionEvent(){let e={spaceId:this.currentConfig[l.CONFIG_SPACE_ID],userId:this.currentConfig[l.CONFIG_USER_ID],applicationKey:this.currentConfig[l.CONFIG_APPLICATION_KEY]};this.$emit("check-api-connection-event",e)},getInheritedValue(e){return this.allConfigs.null?.[e]??null}}});var G=`{% block wallee_settings_content_card_channel_config_options %}
	<mt-card class="mt-card"
			 :title="$tc('wallee-settings.settingForm.options.cardTitle')">

		{% block wallee_settings_content_card_channel_config_credentials_card_container %}
			<sw-container>

				{% block wallee_settings_content_card_channel_config_credentials_card_container_settings %}
					<div v-if="actualConfigData" class="wallee-settings-options-fields">

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_space_view_id %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_SPACE_VIEW_ID]"
									:inheritedValue="selectedSalesChannelId === null ? null : allConfigs['null'][CONFIG_SPACE_VIEW_ID]"
									:customInheritationCheckFunction="checkNumberFieldInheritance">
								<template #content="props">
									<mt-number-field
											:name="CONFIG_SPACE_VIEW_ID"
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.options.spaceViewId.label')"
											:helpText="$tc('wallee-settings.settingForm.options.spaceViewId.tooltipText')"
											:disabled="props.isInherited"
											:model-value="props.currentValue"
											@update:model-value="props.updateCurrentValue">
									</mt-number-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_integration %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_INTEGRATION]"
									:inheritedValue="selectedSalesChannelId === null ? null : allConfigs['null'][CONFIG_INTEGRATION]"
									:customInheritationCheckFunction="checkTextFieldInheritance">
								<template #content="props">
									<sw-single-select
											:name="CONFIG_INTEGRATION"
											labelProperty="name"
											valueProperty="id"
											:options="integrationOptions"
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.options.integration.label')"
											:helpText="$tc('wallee-settings.settingForm.options.integration.tooltipText')"
											:disabled="props.isInherited"
											:value="props.currentValue"
											@update:value="props.updateCurrentValue">
									</sw-single-select>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_line_item_consistency_enabled %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_LINE_ITEM_CONSISTENCY_ENABLED]"
									:inheritedValue="selectedSalesChannelId == null ? null : allConfigs['null'][CONFIG_LINE_ITEM_CONSISTENCY_ENABLED]"
									:customInheritationCheckFunction="checkBoolFieldInheritance">
								<template #content="props">
									<sw-switch-field
											:name="CONFIG_LINE_ITEM_CONSISTENCY_ENABLED"
											bordered
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.options.lineItemConsistencyEnabled.label')"
											:helpText="$tc('wallee-settings.settingForm.options.lineItemConsistencyEnabled.tooltipText')"
											:disabled="props.isInherited"
											:value="props.currentValue"
											@update:value="props.updateCurrentValue">
									</sw-switch-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_email_enabled %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_EMAIL_ENABLED]"
									:inheritedValue="selectedSalesChannelId == null ? null : allConfigs['null'][CONFIG_EMAIL_ENABLED]"
									:customInheritationCheckFunction="checkBoolFieldInheritance">
								<template #content="props">
									<sw-switch-field
											:name="CONFIG_EMAIL_ENABLED"
											bordered
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.options.emailEnabled.label')"
											:helpText="$tc('wallee-settings.settingForm.options.emailEnabled.tooltipText')"
											:disabled="props.isInherited"
											:value="props.currentValue"
											@update:value="props.updateCurrentValue">
									</sw-switch-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}

						{% block wallee_settings_content_card_channel_config_credentials_card_container_settings_order_close_enabled %}
							<sw-inherit-wrapper
									v-model:value="actualConfigData[CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN]"
									:inheritedValue="selectedSalesChannelId == null ? null : allConfigs['null'][CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN]"
									:customInheritationCheckFunction="checkBoolFieldInheritance">
								<template #content="props">
									<sw-switch-field
											:name="CONFIG_KEEP_FAILED_PAYMENTS_ORDER_OPEN"
											bordered
											:mapInheritance="props"
											:label="$tc('wallee-settings.settingForm.options.orderCloseEnabled.label')"
											:helpText="$tc('wallee-settings.settingForm.options.orderCloseEnabled.tooltipText')"
											:disabled="props.isInherited"
											:value="props.currentValue"
											@update:value="props.updateCurrentValue">
									</sw-switch-field>
								</template>
							</sw-inherit-wrapper>
						{% endblock %}
					</div>
				{% endblock %}
			</sw-container>
		{% endblock %}
	</mt-card>

{% endblock %}
`;var{Component:tt,Mixin:at}=Shopware;tt.register("sw-wallee-options",{template:G,name:"WalleeOptions",mixins:[at.getByName("notification")],props:{actualConfigData:{type:Object,required:!0},allConfigs:{type:Object,required:!0},selectedSalesChannelId:{required:!0},isLoading:{type:Boolean,required:!0}},data(){return{...l}},computed:{integrationOptions(){return[{id:"payment_page",name:this.$tc("wallee-settings.settingForm.options.integration.options.payment_page")},{id:"iframe",name:this.$tc("wallee-settings.settingForm.options.integration.options.iframe")}]}},methods:{checkTextFieldInheritance(e){return typeof e!="string"?!0:e.length<=0},checkNumberFieldInheritance(e){return typeof e!="number"?!0:e.length<=0},checkBoolFieldInheritance(e){return typeof e!="boolean"}}});var V=`{% block wallee_settings_icon %}
    <span class="mt-icon icon--wallee-multicolor mt-icon--multicolor" style="width: 16px; height: 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" contentScriptType="text/ecmascript" zoomAndPan="magnify" contentStyleType="text/css" enable-background="new 0 0 1000 1000" version="1.1" xml:space="preserve" width="1000px" preserveAspectRatio="xMidYMid meet" viewBox="0 0 1000 1000" height="1000px" x="0px" y="0px">
<g id="Layer_1">
</g>
<g id="Layer_1_copy">
</g>
<g id="Layer_1_copy_2">
	<g>
		<linearGradient x1="500" x2="500" y1="1000" gradientUnits="userSpaceOnUse" y2="4.882812e-04" xlink:type="simple" xlink:actuate="onLoad" id="SVGID_1_" xlink:show="other">
			<stop style="stop-color:#5BBFF3" offset="0"/>
			<stop style="stop-color:#6FD7EF" offset="1"/>
		</linearGradient>
		<rect fill="url(#SVGID_1_)" height="1000" width="1000"/>
		<path fill="#FFFFFF" d="M746.242,617.103c21.412-26.253,33.855-60.791,33.855-110.539V329.008h-89.82v181.017    c0,30.395-6.215,45.592-16.574,57.343c-13.818,15.196-33.16,20.717-50.443,20.717c-29.262,0-53.744-15.418-69.037-30.395    c-5.533-9.652-8.855-22.418-9.445-41.654c0.146-3.154,0.416-6.138,0.416-9.472V329.008h-0.57H455.38h-0.572v177.556    c0,3.334,0.27,6.317,0.417,9.472c-0.592,19.236-3.912,32.002-9.444,41.654c-15.288,14.977-39.776,30.395-69.038,30.395    c-17.282,0-36.624-5.521-50.438-20.717c-10.365-11.751-16.58-26.948-16.58-57.343V329.008h-89.821v177.556    c0,49.748,12.443,84.286,33.856,110.539c26.946,33.16,71.162,53.89,117.448,53.89c49.75,0,91.886-24.179,118.84-52.502    l9.947-7.153l9.966,7.153c26.947,28.323,69.086,52.502,118.834,52.502C675.078,670.992,719.293,650.263,746.242,617.103    L746.242,617.103z"/>
	</g>
</g>
<g id="Layer_1_copy_3">
</g>
</svg>

    </span>
{% endblock %}
`;var{Component:it}=Shopware;it.register("sw-wallee-settings-icon",{template:V});var q=`<mt-card class="mt-card"
		 :title="$tc('wallee-settings.settingForm.storefrontOptions.cardTitle')">
	<sw-container>
		<div v-if="actualConfigData" class="wallee-settings-storefront-options-fields">
			<sw-inherit-wrapper
					v-model:value="actualConfigData[CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED]"
					:inheritedValue="selectedSalesChannelId == null ? null : allConfigs['null'][CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED]"
					:customInheritationCheckFunction="checkBoolFieldInheritance">
				<template #content="props">
					<sw-switch-field
							:name="CONFIG_STOREFRONT_INVOICE_DOWNLOAD_ENABLED"
							bordered
							:mapInheritance="props"
							:label="$tc('wallee-settings.settingForm.storefrontOptions.invoiceDownloadEnabled.label')"
							:helpText="$tc('wallee-settings.settingForm.storefrontOptions.invoiceDownloadEnabled.tooltipText')"
							:disabled="props.isInherited"
							:value="props.currentValue"
							@update:value="props.updateCurrentValue">
					</sw-switch-field>
				</template>
			</sw-inherit-wrapper>
		</div>
	</sw-container>
</mt-card>

`;var{Component:ot,Mixin:st}=Shopware;ot.register("sw-wallee-storefront-options",{template:q,name:"WalleeStorefrontOptions",mixins:[st.getByName("notification")],props:{actualConfigData:{type:Object,required:!0},allConfigs:{type:Object,required:!0},selectedSalesChannelId:{required:!0},isLoading:{type:Boolean,required:!0}},data(){return{...l}},methods:{checkTextFieldInheritance(e){return typeof e!="string"?!0:e.length<=0},checkNumberFieldInheritance(e){return typeof e!="number"?!0:e.length<=0},checkBoolFieldInheritance(e){return typeof e!="boolean"}}});var z=`<mt-card class="mt-card"
		 :title="$tc('wallee-settings.settingForm.advancedOptions.cardTitle')">
	<sw-container>
		<div v-if="actualConfigData" class="wallee-settings-advanced-options-fields">
			<sw-inherit-wrapper
					v-model:value="actualConfigData[CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED]"
					:inheritedValue="selectedSalesChannelId == null ? null : allConfigs['null'][CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED]"
					:customInheritationCheckFunction="checkBoolFieldInheritance">
				<template #content="props">
					<sw-switch-field
							:name="CONFIG_STOREFRONT_WEBHOOKS_UPDATE_ENABLED"
							bordered
							:mapInheritance="props"
							:label="$tc('wallee-settings.settingForm.advancedOptions.webhooksUpdateEnabled.label')"
							:helpText="$tc('wallee-settings.settingForm.advancedOptions.webhooksUpdateEnabled.tooltipText')"
							:disabled="props.isInherited"
							:value="props.currentValue"
							@update:value="props.updateCurrentValue">
					</sw-switch-field>
				</template>
			</sw-inherit-wrapper>

			<sw-inherit-wrapper
					v-model:value="actualConfigData[CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED]"
					:inheritedValue="selectedSalesChannelId == null ? null : allConfigs['null'][CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED]"
					:customInheritationCheckFunction="checkBoolFieldInheritance">
				<template #content="props">
					<sw-switch-field
							:name="CONFIG_STOREFRONT_PAYMENTS_UPDATE_ENABLED"
							bordered
							:mapInheritance="props"
							:label="$tc('wallee-settings.settingForm.advancedOptions.paymentsUpdateEnabled.label')"
							:helpText="$tc('wallee-settings.settingForm.advancedOptions.paymentsUpdateEnabled.tooltipText')"
							:disabled="props.isInherited"
							:value="props.currentValue"
							@update:value="props.updateCurrentValue">
					</sw-switch-field>
				</template>
			</sw-inherit-wrapper>
		</div>
	</sw-container>
</mt-card>

`;var{Component:ct,Mixin:dt}=Shopware;ct.register("sw-wallee-advanced-options",{template:z,name:"WalleeAdvancedOptions",inject:["acl"],mixins:[dt.getByName("notification")],props:{actualConfigData:{type:Object,required:!0},allConfigs:{type:Object,required:!0},selectedSalesChannelId:{required:!0},isLoading:{type:Boolean,required:!0}},data(){return{...l}},methods:{checkTextFieldInheritance(e){return typeof e!="string"?!0:e.length<=0},checkNumberFieldInheritance(e){return typeof e!="number"?!0:e.length<=0},checkBoolFieldInheritance(e){return typeof e!="boolean"}}});var U={"sw-privileges":{permissions:{parents:{wallee:"Wallee plugin"},wallee:{label:"Wallee berechtigungen"}}},"wallee-settings":{general:{descriptionTextModule:"Wallee-Einstellungen",mainMenuItemGeneral:"Wallee"},header:"Wallee",messageNotBlank:"Dieser Wert sollte nicht leer sein.",salesChannelCard:{button:{description:"Klicken Sie auf diese Schaltfl\xE4che, um Wallee als Standard-Zahlungsabwickler im ausgew\xE4hlten Vertriebskanal festzulegen",label:"Wallee als Standard-Zahlungsabwickler festlegen"},messageDefaultPaymentError:"Wallee als Standard-Zahlungsabwickler konnte nicht festgelegt werden..",messageDefaultPaymentUpdated:"Wallee als Standard-Zahlungsabwickler wurde festgelegt."},settingForm:{credentials:{applicationKey:{label:"Application Key",tooltipText:"Der Anwendungsschl\xFCssel wird verwendet, um dieses Plugin mit der API Wallee zu authentifizieren."},cardTitle:"Anmeldedaten",spaceId:{label:"Space ID",tooltipText:"Die Space ID wird verwendet, um dieses Plugin mit der API Wallee zu authentifizieren."},userId:{label:"User ID",tooltipText:"Die Benutzer-ID wird verwendet, um dieses Plugin mit der Wallee-API zu authentifizieren."},button:{description:"Klicken Sie auf diese Schaltfl\xE4che, um die Wallee API zu testen",label:"API Verbindung testen"},alert:{title:"API-Test",successMessage:"Die Verbindung wurde erfolgreich getestet.",errorMessage:"Die Verbindung ist fehlgeschlagen. Versuchen Sie es erneut."}},messageSaveSuccess:"Wallee-Einstellungen wurden gespeichert.",messageOrderDeliveryStateError:"Wallee OrderDeliveryState konnte nicht gespeichert werden.",messageOrderDeliveryStateUpdated:"Wallee OrderDeliveryState wurde aktualisiert.",messagePaymentMethodConfigurationError:"Wallee PaymentMethodConfiguration konnte nicht gespeichert werden. Bitte \xFCberpr\xFCfen Sie Ihre Anmeldedaten.",messagePaymentMethodConfigurationUpdated:"Wallee PaymentMethodConfiguration wurde registriert.",messageWebHookError:"Wallee WebHook konnte nicht gespeichert werden. Bitte \xFCberpr\xFCfen Sie Ihre Zugangsdaten.",messageWebHookUpdated:"Wallee WebHook wurde aktualisiert.",options:{cardTitle:"Optionen",emailEnabled:{label:"Auftragsbest\xE4tigung per E-Mail senden",tooltipText:"Wenn diese Einstellung aktiviert ist, erhalten Ihre Kunden eine E-Mail von Ihrem Gesch\xE4ft, wenn die Zahlung ihrer Bestellung autorisiert ist."},orderCloseEnabled:{label:"Bestellung bei fehlgeschlagener Zahlung offen halten",tooltipText:"Wenn diese Einstellung aktiviert ist, bleibt die Bestellung auch bei fehlgeschlagenen Zahlungen offen."},integration:{label:"Integration",options:{iframe:"Iframe",payment_page:"Payment Page"},tooltipText:"Integration"},lineItemConsistencyEnabled:{label:"Konsistenz der Einzelposten",tooltipText:"Wenn diese Option aktiviert ist, stimmen die Summen der Einzelposten in WalleePayment immer mit der Shopware-Bestellsumme \xFCberein."},spaceViewId:{label:"Space View ID",tooltipText:"Space View ID"}},save:"Speichern",storefrontOptions:{cardTitle:"Storefront-Optionen",invoiceDownloadEnabled:{label:"Rechnung Download",tooltipText:"Wenn diese Einstellung aktiviert ist, k\xF6nnen Ihre Kunden Auftragsrechnungen von Wallee herunterladen."}},advancedOptions:{cardTitle:"Erweiterte-Optionen",webhooksUpdateEnabled:{label:"Webhooks-Update",tooltipText:"Wenn diese Einstellung aktiviert ist, wird das Webhook-Update ausgel\xF6st, wenn Sie die Einstellungen speichern"},paymentsUpdateEnabled:{label:"Payments-Update",tooltipText:"Wenn diese Einstellung aktiviert ist, wird die Aktualisierung der Zahlungsmethoden ausgel\xF6st, wenn Sie die Einstellungen speichern"}},titleError:"Fehler",titleSuccess:"Erfolg"}}};var H={"sw-privileges":{permissions:{parents:{wallee:"Wallee plugin"},wallee:{label:"Wallee permissions"}}},"wallee-settings":{general:{descriptionTextModule:"Wallee settings",mainMenuItemGeneral:"Wallee"},header:"Wallee",messageNotBlank:"This value should not be blank.",salesChannelCard:{button:{description:"Click this button to set Wallee as default payment handler in the selected SalesChannel",label:"Set Wallee as default payment handler"},messageDefaultPaymentError:"Wallee as default payment could not be set.",messageDefaultPaymentUpdated:"Wallee as default payment has been set."},settingForm:{credentials:{applicationKey:{label:"Application Key",tooltipText:"The Application Key is used to authenticate this plugin with the Wallee API."},cardTitle:"Credentials",spaceId:{label:"Space ID",tooltipText:"The space ID is used to authenticate this plugin with the Wallee API."},userId:{label:"User ID",tooltipText:"The user ID is used to authenticate this plugin with the Wallee API."},button:{description:"Click this button to test the Wallee API",label:"API connection test"},alert:{title:"API Test",successMessage:"The connection was successfully tested.",errorMessage:"The connection was failed. Try it again."}},messageSaveSuccess:"Wallee settings have been saved.",messageOrderDeliveryStateError:"Wallee OrderDeliveryState could not be saved.",messageOrderDeliveryStateUpdated:"Wallee OrderDeliveryState has been updated.",messagePaymentMethodConfigurationError:"Wallee PaymentMethodConfiguration could not be saved. Please check your credentials.",messagePaymentMethodConfigurationUpdated:"Wallee PaymentMethodConfiguration has been registered.",messageWebHookError:"Wallee WebHook could not be saved. Please check your credentials.",messageWebHookUpdated:"Wallee WebHook has been updated.",messageHeadlessIntegrationError:"Iframe integration is only supported for Storefront Sales Channels.",messageGlobalIframeError:"Iframe integration cannot be set globally because you have non-Storefront Sales Channels.",options:{cardTitle:"Options",emailEnabled:{label:"Send order confirmation email",tooltipText:"If this setting is enabled your customers will receive an email from your store when their order payment is authorised"},orderCloseEnabled:{label:"Keep order open on failed payment",tooltipText:"If this setting is enabled the order will be kept open for failed payments"},integration:{label:"Integration",options:{iframe:"Iframe",payment_page:"Payment Page"},tooltipText:"Integration"},lineItemConsistencyEnabled:{label:"Line item consistency",tooltipText:"If this option is enabled line item totals in WalleePayment will always match Shopware order total"},spaceViewId:{label:"Space View ID",tooltipText:"Space View ID"}},save:"Save",storefrontOptions:{cardTitle:"Storefront Options",invoiceDownloadEnabled:{label:"Invoice Download",tooltipText:"If this setting is enabled your customers will be able to download order invoices from Wallee"}},advancedOptions:{cardTitle:"Advanced Options",webhooksUpdateEnabled:{label:"Webhooks Update",tooltipText:"If this setting is enabled webhook update will be triggered when you save settings"},paymentsUpdateEnabled:{label:"Payments Update",tooltipText:"If this setting is enabled payment methods update will be triggered when you save settings"}},titleError:"Error",titleSuccess:"Success"}}};var K={"sw-privileges":{permissions:{parents:{wallee:"Wallee brancher"},wallee:{label:"Wallee autorisations"}}},"wallee-settings":{general:{descriptionTextModule:"Param\xE8tres de Wallee",mainMenuItemGeneral:"Wallee"},header:"Wallee",messageNotBlank:"Cette valeur ne doit pas \xEAtre vide.",salesChannelCard:{button:{description:"Cliquez sur ce bouton pour d\xE9finir Wallee comme gestionnaire de paiement par d\xE9faut dans le canal de vente s\xE9lectionn\xE9.",label:"D\xE9finir Wallee comme gestionnaire de paiement par d\xE9faut"},messageDefaultPaymentError:"Wallee comme paiement par d\xE9faut n'a pas pu \xEAtre d\xE9fini.",messageDefaultPaymentUpdated:"Wallee comme paiement par d\xE9faut a \xE9t\xE9 d\xE9fini."},settingForm:{credentials:{applicationKey:{label:"Application Key",tooltipText:"La cl\xE9 d'application est utilis\xE9e pour authentifier ce plugin avec l'API."},cardTitle:"R\xE9f\xE9rences",spaceId:{label:"Space ID",tooltipText:"L'ID de l'espace est utilis\xE9 pour authentifier ce plugin avec l'API Wallee.."},userId:{label:"User ID",tooltipText:"L'ID utilisateur est utilis\xE9 pour authentifier ce plugin avec l'API Wallee."},button:{description:"Cliquez sur ce bouton pour tester l'API Wallee.",label:"Test de connexion \xE0 l'API"},alert:{title:"Test API",successMessage:"La connexion a \xE9t\xE9 test\xE9e avec succ\xE8s.",errorMessage:"La connexion a \xE9chou\xE9. R\xE9essayez."}},messageSaveSuccess:"Les param\xE8tres de Wallee ont \xE9t\xE9 enregistr\xE9s.",messageOrderDeliveryStateError:"Les param\xE8tres de Wallee OrderDeliveryState n'ont pas pu \xEAtre enregistr\xE9s.",messageOrderDeliveryStateUpdated:"Wallee OrderDeliveryState a \xE9t\xE9 mis \xE0 jour.",messagePaymentMethodConfigurationError:"Wallee PaymentMethodConfiguration n'a pas pu \xEAtre enregistr\xE9. Veuillez v\xE9rifier vos informations d'identification.",messagePaymentMethodConfigurationUpdated:"Wallee PaymentMethodConfiguration a \xE9t\xE9 enregistr\xE9.",messageWebHookError:"Wallee WebHook n'a pas pu \xEAtre enregistr\xE9. Veuillez v\xE9rifier vos informations d'identification.",messageWebHookUpdated:"Wallee WebHook a \xE9t\xE9 mis \xE0 jour.",options:{cardTitle:"Options",emailEnabled:{label:"Envoyer un e-mail de confirmation de commande",tooltipText:"Si ce param\xE8tre est activ\xE9, vos clients recevront un e-mail de votre magasin lorsque le paiement de leur commande sera autoris\xE9"},orderCloseEnabled:{label:"Garder la commande ouverte en cas de paiement \xE9chou\xE9",tooltipText:"Si ce param\xE8tre est activ\xE9, la commande sera gard\xE9e ouverte en cas d'\xE9chec de paiement."},integration:{label:"Integration",options:{iframe:"Iframe",payment_page:"Page de paiement"},tooltipText:"Integration"},lineItemConsistencyEnabled:{label:"Coh\xE9rence des postes de ligne",tooltipText:"Si cette option est activ\xE9e, les totaux des articles dans WalleePayment correspondront toujours au total de la commande Shopware."},spaceViewId:{label:"Space View ID",tooltipText:"Space View ID"}},save:"Enregistrer",storefrontOptions:{cardTitle:"Storefront Options",invoiceDownloadEnabled:{label:"T\xE9l\xE9chargement de facture",tooltipText:"Si ce param\xE8tre est activ\xE9, vos clients pourront t\xE9l\xE9charger les factures de commande depuis Wallee"}},advancedOptions:{cardTitle:"Options avanc\xE9es",webhooksUpdateEnabled:{label:"Mise \xE0 jour des webhooks",tooltipText:"Si ce param\xE8tre est activ\xE9, la mise \xE0 jour des webhooks sera d\xE9clench\xE9e lorsque vous enregistrerez les param\xE8tres."},paymentsUpdateEnabled:{label:"Mise \xE0 jour des paiements",tooltipText:"Si ce param\xE8tre est activ\xE9, la mise \xE0 jour des m\xE9thodes de paiement sera d\xE9clench\xE9e lorsque vous enregistrez les param\xE8tres."}},titleError:"Erreur",titleSuccess:"Succ\xE8s"}}};var Q={"sw-privileges":{permissions:{parents:{wallee:"Wallee brancher"},wallee:{label:"Wallee autorisations"}}},"wallee-settings":{general:{descriptionTextModule:"Impostazioni Wallee",mainMenuItemGeneral:"Wallee"},header:"Wallee",messageNotBlank:"Questo valore non dovrebbe essere vuoto.",salesChannelCard:{button:{description:"Fai clic su questo pulsante per impostare Wallee come gestore di pagamento predefinito nel SalesChannel selezionato",label:"Imposta Wallee come gestore di pagamento predefinito"},messageDefaultPaymentError:"Non \xE8 stato possibile impostare Wallee come pagamento predefinito.",messageDefaultPaymentUpdated:"Wallee come pagamento predefinito \xE8 stato impostato."},settingForm:{credentials:{applicationKey:{label:"Chiave di applicazione",tooltipText:"La chiave dell'applicazione \xE8 usata per autenticare questo plugin con l'API Wallee."},cardTitle:"Credenziali",spaceId:{label:"ID spazio",tooltipText:"L'ID dello spazio \xE8 usato per autenticare questo plugin con l'API Wallee."},userId:{label:"ID utente",tooltipText:"L'ID utente \xE8 usato per autenticare questo plugin con l'API Wallee."},button:{description:"Fare clic su questo pulsante per testare l'API Wallee.",label:"Test di connessione API"},alert:{title:"Test API",successMessage:"La connessione \xE8 stata testata con successo.",errorMessage:"La connessione \xE8 fallita. Riprovare."}},messageSaveSuccess:"Le impostazioni di Wallee sono state salvate.",messageOrderDeliveryStateError:"Wallee OrderDeliveryState non pu\xF2 essere salvato.",messageOrderDeliveryStateUpdated:"Wallee OrderDeliveryState \xE8 stato aggiornato.",messagePaymentMethodConfigurationError:"Wallee PaymentMethodConfiguration non pu\xF2 essere salvato. Per favore controlla le tue credenziali.",messagePaymentMethodConfigurationUpdated:"Wallee PaymentMethodConfiguration \xE8 stato registrato.",messageWebHookError:"Wallee WebHook non pu\xF2 essere salvato. Per favore controlla le tue credenziali.",messageWebHookUpdated:"Wallee WebHook \xE8 stato aggiornato.",options:{cardTitle:"Opzioni",emailEnabled:{label:"Invia email di conferma dell'ordine",tooltipText:"Se questa impostazione \xE8 abilitata i tuoi clienti riceveranno un'email dal tuo negozio quando il pagamento del loro ordine sar\xE0 autorizzato"},orderCloseEnabled:{label:"Mantieni l'ordine aperto in caso di pagamento non riuscito.",tooltipText:"Se questa impostazione \xE8 abilitata, l'ordine rimarr\xE0 aperto in caso di mancato pagamento."},integration:{label:"Integrazione",options:{iframe:"Iframe",payment_page:"Pagina di pagamento"},tooltipText:"Integrazione"},lineItemConsistencyEnabled:{label:"Coerenza dell'elemento linea",tooltipText:"Se questa opzione \xE8 abilitata i totali degli articoli in WalleePayment corrisponderanno sempre al totale dell'ordine Shopware"},spaceViewId:{label:"ID della vista spazio",tooltipText:"ID della vista spaziale"}},save:"Salva",storefrontOptions:{cardTitle:"Opzioni vetrina",invoiceDownloadEnabled:{label:"Scaricamento fattura",tooltipText:"Se questa impostazione \xE8 abilitata i tuoi clienti potranno scaricare le fatture degli ordini da Wallee"}},advancedOptions:{cardTitle:"Opzioni avanzate",webhooksUpdateEnabled:{label:"Aggiornamento webhooks",tooltipText:"Se questa impostazione \xE8 abilitata l'aggiornamento dei webhook sar\xE0 attivato quando si salvano le impostazioni"},paymentsUpdateEnabled:{label:"Aggiornamento pagamenti",tooltipText:"Se questa impostazione \xE8 abilitata l'aggiornamento dei metodi di pagamento verr\xE0 attivato quando si salvano le impostazioni"}},titleError:"Errore",titleSuccess:"Successo"}}};var{Module:ft}=Shopware;ft.register("wallee-settings",{type:"plugin",name:"Wallee",title:"wallee-settings.general.descriptionTextModule",description:"wallee-settings.general.descriptionTextModule",color:"#28d8ff",icon:"default-action-settings",version:"1.0.1",targetVersion:"1.0.1",snippets:{"de-DE":U,"en-GB":H,"fr-FR":K,"it-IT":Q},routes:{index:{component:"wallee-settings",path:"index",meta:{parentPath:"sw.settings.index",privilege:"wallee.viewer"},props:{default:e=>({hash:e.params.hash})}}},settingsItem:{group:"plugins",to:"wallee.settings.index",iconComponent:"sw-wallee-settings-icon",backgroundEnabled:!0,privilege:"wallee.viewer"}});var p=Shopware.Classes.ApiService,f=class extends p{constructor(t,a,n="wallee"){super(t,a,n)}registerWebHooks(t=null){let a=this.getBasicHeaders(),n=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/register-web-hooks`;return this.httpClient.post(n,{salesChannelId:t},{headers:a}).then(i=>p.handleResponse(i))}checkApiConnection(t=null,a=null,n=null){let i=this.getBasicHeaders(),s=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/check-api-connection`;return this.httpClient.post(s,{spaceId:t,userId:a,applicationId:n},{headers:i}).then(o=>p.handleResponse(o))}setWalleeAsSalesChannelPaymentDefault(t=null){let a=this.getBasicHeaders(),n=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/set-wallee-as-sales-channel-payment-default`;return this.httpClient.post(n,{salesChannelId:t},{headers:a}).then(i=>p.handleResponse(i))}synchronizePaymentMethodConfiguration(t=null){let a=this.getBasicHeaders(),n=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/synchronize-payment-method-configuration`;return this.httpClient.post(n,{salesChannelId:t},{headers:a}).then(i=>p.handleResponse(i))}installOrderDeliveryStates(){let t=this.getBasicHeaders(),a=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/install-order-delivery-states`;return this.httpClient.post(a,{},{headers:t}).then(n=>p.handleResponse(n))}},Y=f;var h=Shopware.Classes.ApiService,g=class extends h{constructor(t,a,n="wallee"){super(t,a,n)}createRefund(t,a,n,i){let s=this.getBasicHeaders(),o=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/refund/create-refund/`;return this.httpClient.post(o,{salesChannelId:t,transactionId:a,quantity:n,lineItemId:i},{headers:s}).then(r=>h.handleResponse(r))}createRefundByAmount(t,a,n){let i=this.getBasicHeaders(),s=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/refund/create-refund-by-amount/`;return this.httpClient.post(s,{salesChannelId:t,transactionId:a,refundableAmount:n},{headers:i}).then(o=>h.handleResponse(o))}createPartialRefund(t,a,n,i){let s=this.getBasicHeaders(),o=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/refund/create-partial-refund/`;return this.httpClient.post(o,{salesChannelId:t,transactionId:a,refundableAmount:n,lineItemId:i},{headers:s}).then(r=>h.handleResponse(r))}},Z=g;var j=Shopware.Classes.ApiService,b=class extends j{constructor(t,a,n="wallee"){super(t,a,n)}getTransactionData(t,a){let n=this.getBasicHeaders(),i=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/transaction/get-transaction-data/`;return this.httpClient.post(i,{salesChannelId:t,transactionId:a},{headers:n}).then(s=>j.handleResponse(s))}getInvoiceDocument(t,a){return`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/transaction/get-invoice-document/${t}/${a}`}getPackingSlip(t,a){return`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/transaction/get-packing-slip/${t}/${a}`}},J=b;var X=Shopware.Classes.ApiService,_=class extends X{constructor(t,a,n="wallee"){super(t,a,n)}createTransactionCompletion(t,a){let n=this.getBasicHeaders(),i=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/transaction-completion/create-transaction-completion/`;return this.httpClient.post(i,{salesChannelId:t,transactionId:a},{headers:n}).then(s=>X.handleResponse(s))}},ee=_;var te=Shopware.Classes.ApiService,w=class extends te{constructor(t,a,n="wallee"){super(t,a,n)}createTransactionVoid(t,a){let n=this.getBasicHeaders(),i=`${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/transaction-void/create-transaction-void/`;return this.httpClient.post(i,{salesChannelId:t,transactionId:a},{headers:n}).then(s=>te.handleResponse(s))}},ae=w;var{Application:u}=Shopware;u.addServiceProvider("WalleeConfigurationService",e=>{let t=u.getContainer("init");return new Y(t.httpClient,e.loginService)});u.addServiceProvider("WalleeRefundService",e=>{let t=u.getContainer("init");return new Z(t.httpClient,e.loginService)});u.addServiceProvider("WalleeTransactionService",e=>{let t=u.getContainer("init");return new J(t.httpClient,e.loginService)});u.addServiceProvider("WalleeTransactionCompletionService",e=>{let t=u.getContainer("init");return new ee(t.httpClient,e.loginService)});u.addServiceProvider("WalleeTransactionVoidService",e=>{let t=u.getContainer("init");return new ae(t.httpClient,e.loginService)});})();
