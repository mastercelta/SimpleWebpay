<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CaptureHandler implements HandlerInterface
{
    const TXN_ID = 'TXN_ID';
    const FORCE_RESULT = 'FORCE_RESULT';
    const TXN_TYPE = 'TXN_TYPE';
    const CODE = 'swppayment';
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_messageManager = $messageManager;
    }
    
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
        $order = $paymentDO->getOrder();
        //print_r(get_class_methods($order));
        //print_r($order->getid());
        //die();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Magento\Sales\Model\Order\Payment\Transaction')->getCollection();
        
        $data = $model->addAttributeToFilter('order_id', array('eq' => $order->getid()))
                ->addAttributeToFilter('txn_type', array('eq' => \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH));

        $data = json_decode(json_encode($data->toArray()), FALSE);
        
        $ReferenceNumber = "";
        
        $ResponseSave = new \stdClass();
       // $this->config->getValue('payment_action');
        $thisss = $payment->getMethodInstance(self::CODE);
        
        $isForce = false;
        $transaction = null;
        foreach ($data->items as $key => $item) {
            if ($item->is_closed == 0) {
                $isForce = true;
                $transaction = $item;
            }
        }
        
        if ($isForce) {
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

                $chProcess = curl_init($thisss->getConfigData('url')."/api/Force/");

                curl_setopt($chProcess, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($chProcess, CURLOPT_POST, 1);

                $postProces = "verifyingpost=".$ResponseSave->Data;
                $postProces .= "&referencenumber=".$ReferenceNumber;

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
                    "Message","RecurringTokenId","InvoiceNumber","OriginalAmount");
                foreach ($ResponseSave as $key => $value) {
                    if(in_array($key, $inarray) && $key !="extension_attributes") {
                     //   array_push($newData, $value)
                        $payment->setAdditionalInformation("Force".$key, $value);
                    }
                }
            }else {
                $this->_messageManager->addError($ResponseSave->Message);
                throw new \InvalidArgumentException('Payment data object should be provided');
            }
        }else{
        
        $Is3dsecure = $payment->getAdditionalInformation("ReferenceNumber");
        
        if($payment->getAdditionalInformation("ReferenceNumber") != "" && $payment->getAdditionalInformation("ReferenceNumber") != NULL){
            $ResponseSave->Message =  $payment->getAdditionalInformation("Message");
            $ResponseSave->Result =  $payment->getAdditionalInformation("Result");
        }else{
            try{
                $ip = $_SERVER["REMOTE_ADDR"];
                if($thisss->getConfigData('url') == null || $thisss->getConfigData('url') == "" ){
                    throw new \Exception("The url credit card must be configured");
                }

                $RecurringSaleTokenId = $payment->getAdditionalInformation("webpayrecurringsaletokenid");

                if(empty($RecurringSaleTokenId)) {
                    throw new \Exception("the data crypto cant be empty");
                }

                $billingAddressInfo = $order->getBillingAddress();
                $amount = $order->getGrandTotalAmount();
                $invoice = $order->getOrderIncrementId();

                $ch = curl_init($thisss->getConfigData('url')."/?app=genericcontroller&action=siteVerify");
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($ch, CURLOPT_POST, 1);

                $postSend = "secretkey=".$thisss->getConfigData('secretkey');
                $postSend .= "&merchant=".$thisss->getConfigData('merchantid');
                $postSend .= "&address=".$billingAddressInfo->getStreetLine1();
                $postSend .= "&amount=".$amount;
                $postSend .= "&invoicenumber=".$invoice;
                $postSend .= "&state=".$billingAddressInfo->getRegionCode();
                $postSend .= "&city=".$billingAddressInfo->getCity();
                $postSend .= "&type=Sale";
                $postSend .= "&type3d=FunctionAuto";
                $postSend .= "&cardinalreturn=returnCardinalMag";
                $postSend .= "&zipcode=".$billingAddressInfo->getPostcode();
                $postSend .= "&tokenid=".$RecurringSaleTokenId;
                if(!empty($billingAddressInfo->getCustomerId())) $postSend .= "&customercode=".$billingAddressInfo->getCustomerId();
                $postSend .= "&email=".$billingAddressInfo->getEmail();
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
                $endpoint = (strpos(strtoupper($RecurringSaleTokenId), 'CRYPTO') !== false) ? "UseCrypto" : "UseToken";

                $chProcess = curl_init($thisss->getConfigData('url')."/api/$endpoint/");

                curl_setopt($chProcess, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt ($chProcess, CURLOPT_POST, 1);

                $postProces = "verifyingpost=".$ResponseSave->Data;
                $postProces .= "&tokenid=".$RecurringSaleTokenId;

                curl_setopt ($chProcess, CURLOPT_POSTFIELDS, $postProces);

                curl_setopt($chProcess,CURLOPT_RETURNTRANSFER, true);

                $ResponseSave = curl_exec($chProcess);

                $error = curl_error($chProcess);

                curl_close ($chProcess);
                if(!empty($error))  {
                    throw new \Exception($error);
                }

                $ResponseSave = json_decode($ResponseSave);
                if($ResponseSave->Result !== 0 && $ResponseSave->Result !== 21){
                    throw new \Exception($ResponseSave->Message);
                }
            } catch (\Exception $ex) {
                $ResponseSave->Message = $ex->getMessage();
                $ResponseSave->Result = -1;
            }

            if($ResponseSave->Result === -1 || $ResponseSave->Result === 21){
                echo json_encode($ResponseSave);
                die();
            }
         //   $newData = array();    
            if($ResponseSave->Result === 0){
                $inarray = array("Result","AutorizationNumber","ReferenceNumber","TraceNumber","Amount","CardType",
                    "Message","RecurringTokenId","InvoiceNumber","OriginalAmount");
                foreach ($ResponseSave as $key => $value) {
                    if(in_array($key, $inarray) && $key !="extension_attributes") {
                     //   array_push($newData, $value)
                        $payment->setAdditionalInformation($key, $value);
                    }
                }
            }
        }
        }
    //    $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $newData);
        $payment->setTransactionId($payment->getAdditionalInformation("ReferenceNumber"));
        $payment->setIsTransactionClosed(true);
        
    }
}
