<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;

class CancelHandler implements HandlerInterface
{
    const TXN_ID = 'TXN_ID';
    
    private $subjectReader;

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();
        
          /** @var PaymentDataObjectInterface $paymentDO */
          $order = $paymentDO->getOrder();
          $ResponseSave = new \stdClass();
         // $this->config->getValue('payment_action');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        
        $payment->setIsTransactionClosed(true);
        $payment->setShouldCloseParentTransaction(true);
        
    }
}
