<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Gateway\Response;
use Magento\Sales\Model\Order\Payment;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class VoidHandler implements HandlerInterface
{
    const CODE = 'swppayment';
    const AUTH = 'authorize';
    /**
     * @param Payment $orderPayment
     * @param \Braintree\Transaction $transaction
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_messageManager = $messageManager;
    }
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
        $thisss = $payment->getMethodInstance(self::CODE);
        try{
            $ip = $_SERVER["REMOTE_ADDR"];
            if($thisss->getConfigData('url') == null || $thisss->getConfigData('url') == "" ){
                throw new \Exception("The url credit card must be configured");
            }

            $ReferenceNumber = $payment->getAdditionalInformation("ReferenceNumber");

            if(empty($ReferenceNumber)) {
                throw new \Exception("the referenceNumber cant be empty");
            }

            $ch = curl_init($thisss->getConfigData('url')."/?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);

            $postSend = "secretkey=".$thisss->getConfigData('secretkey');
            $postSend .= "&merchant=".$thisss->getConfigData('merchantid');
            $postSend .= "&referencenumber=".$ReferenceNumber;
            $postSend .= "&ip=$ip";

            curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);

            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            $ResponseSave = curl_exec($ch);

            $error = curl_error($ch);
            curl_close ($ch);
            if(!empty($error))  {
                throw new \Exception($error);
            }

            $ResponseSave = json_decode($ResponseSave);
            if($ResponseSave->Result != 0) {
                throw new \Exception($ResponseSave->Message);
            }
           
            $chProcess = curl_init($thisss->getConfigData('url')."/api/Void/");

            curl_setopt($chProcess, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($chProcess, CURLOPT_POST, 1);
            
            $amount = $order->getGrandTotalAmount();
            
            $postProces = "verifyingpost=".$ResponseSave->Data;
            $postProces .= "&referencenumber=".$ReferenceNumber;
            $postProces .= "&amount=".$amount;
            
            curl_setopt ($chProcess, CURLOPT_POSTFIELDS, $postProces);

            curl_setopt($chProcess,CURLOPT_RETURNTRANSFER, true);

            $ResponseSave = curl_exec($chProcess);

            $error = curl_error($chProcess);

            curl_close ($chProcess);
            if(!empty($error))  {
                throw new \Exception($error);
            }

            $ResponseSave = json_decode($ResponseSave);
            if($ResponseSave->Result !== 0){
                throw new \Exception($ResponseSave->Message);
            }
        } catch (\Exception $ex) {
            $ResponseSave->Message = $ex->getMessage();
            $ResponseSave->Result = -1;
        }
        
        if($ResponseSave->Result === 0){
            $inarray = array("Result","AutorizationNumber","ReferenceNumber","TraceNumber","Amount","CardType",
                "Message","InvoiceNumber","OriginalAmount");

            $newData = array();
            foreach ($ResponseSave as $key => $value) {
                if(in_array($key, $inarray)){
                    $newData[$key] = $value;
                }
            }
            $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $newData);
     //     $payment->setTransactionId($payment->getAdditionalInformation("ReferenceNumber"));
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
        }else {
            $this->_messageManager->addError($ResponseSave->Message);
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
    }
}
