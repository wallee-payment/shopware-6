<?php declare(strict_types=1);

namespace WalleePayment\Core\Storefront\Account\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
  Checkout\Cart\Exception\CustomerNotLoggedInException,
  Checkout\Customer\CustomerEntity,
  PlatformRequest,
  System\SalesChannel\SalesChannelContext
};
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\{
  HttpFoundation\HeaderUtils,
  HttpFoundation\RequestStack,
  HttpFoundation\Response,
  Routing\Attribute\Route,
  Security\Core\Exception\AccessDeniedException
};
use WalleePayment\Core\{
  Api\Transaction\Service\TransactionService,
  Settings\Service\SettingsService
};

#[Package('storefront')]
#[Route(defaults: ['_routeScope' => ['storefront']])]
class AccountOrderController extends StorefrontController
{
    /**
     * @var \WalleePayment\Core\Settings\Service\SettingsService
     */
    protected $settingsService;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \WalleePayment\Core\Api\Transaction\Service\TransactionService
     */
    protected $transactionService;
    
    /**
     * @var RequestStack
     */
    protected $requestStack;
    
    /**
     * AccountOrderController constructor.
     * @param \WalleePayment\Core\Settings\Service\SettingsService $settingsService
     * @param \WalleePayment\Core\Api\Transaction\Service\TransactionService $transactionService
     * @param RequestStack $requestStack
     */
    public function __construct(SettingsService $settingsService, TransactionService $transactionService, RequestStack $requestStack)
    {
        $this->settingsService = $settingsService;
        $this->transactionService = $transactionService;
        $this->requestStack = $requestStack;
    }
    
    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @internal
     * @required
     *
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Download invoice document
     *
     * @param string $orderId
     * @param \Shopware\Core\System\SalesChannel\SalesChannelContext $salesChannelContext
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Wallee\Sdk\ApiException
     * @throws \Wallee\Sdk\Http\ConnectionException
     * @throws \Wallee\Sdk\VersioningException
     */
    #[Route("/wallee/account/order/download/invoice/document/{orderId}",
      name: "frontend.wallee.account.order.download.invoice.document",
      methods: ['GET'])]
    public function downloadInvoiceDocument(string $orderId, SalesChannelContext $salesChannelContext): Response
    {
        $customer = $this->getLoggedInCustomer();
        $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());
        $transactionEntity = $this->transactionService->getByOrderId($orderId, $salesChannelContext->getContext());
        if (strcasecmp($customer->getCustomerNumber(), $transactionEntity->getData()['customerId']) != 0) {
            throw new AccessDeniedException();
        }
        $invoiceDocument = $settings->getApiClient()->getTransactionService()->getInvoiceDocument($settings->getSpaceId(), $transactionEntity->getTransactionId());
        $forceDownload = true;
        $filename = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $invoiceDocument->getTitle()) . '.pdf';
        $disposition = HeaderUtils::makeDisposition(
          $forceDownload ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
          $filename,
          $filename
        );
        $response = new Response(base64_decode($invoiceDocument->getData()));
        $response->headers->set('Content-Type', $invoiceDocument->getMimeType());
        $response->headers->set('Content-Disposition', $disposition);
        
        return $response;
    }
    
    /**
     * @return CustomerEntity
     */
    protected function getLoggedInCustomer(): CustomerEntity
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            throw new CustomerNotLoggedInException();
        }
        
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        
        if ($context && $context->getCustomer() && $context->getCustomer()->getGuest() === false) {
            return $context->getCustomer();
        }
        
        throw new CustomerNotLoggedInException();
    }
}
