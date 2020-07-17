<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Cenpos\SimpleWebpay\Gateway\Http\Client\ClientMock;

class MockDataRequest extends CommonRequest
{
    const FORCE_RESULT = 'FORCE_RESULT';
    const TXN_TYPE = 'TXN_TYPE';
    const CODE = 'swppayment';
    const AUTH = 'authorize';
    protected $ordern;
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
     /**
     * @param ConfigInterface $config
     */

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order $orders
       // \Magento\Sales\Api\Data\OrderInterface $orderinterface
    ) {
        $this->_messageManager = $messageManager;
        $this->ordern = $orders;
    }

    public function build(array $buildSubject)
    {
      //  die("asa");
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
         
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $ResponseSave = new \stdClass();
        
        $Is3dsecure = $payment->getAdditionalInformation("ReferenceNumber");
        
        if($payment->getAdditionalInformation("ReferenceNumber") != "" && $payment->getAdditionalInformation("ReferenceNumber") != NULL){
            $ResponseSave->Message =  $payment->getAdditionalInformation("Message");
            $ResponseSave->Result =  $payment->getAdditionalInformation("Result");
        }else{
            // $this->config->getValue('payment_action');
             $thisss = $payment->getMethodInstance(self::CODE);
             $isEdit = $isReAuth = $isReturn =false;
             try{
                 $ip = $_SERVER["REMOTE_ADDR"];
                 if($thisss->getConfigData('url') == null || $thisss->getConfigData('url') == "" ){
                     throw new \Exception("The url credit card must be configured");
                 }

                
                 $orderedit = explode("-", $order->getOrderIncrementId());
                 $invoice = $orderedit[0];
                 $isEdit = (count($orderedit) > 1);

                 $RecurringSaleTokenId = $payment->getAdditionalInformation("webpayrecurringsaletokenid");
                 $referenceNumber = "";
                 if(empty($RecurringSaleTokenId) && !$isEdit) {
                     throw new \Exception("the data crypto cant be empty");
                 }

                 $billingAddressInfo = $order->getBillingAddress();
                 $amount = $order->getGrandTotalAmount();

                //Check if amount is greater that original in case edit order

                if($isEdit){
                    if($orderedit[1] > 1) $invoice .= (string)(number_format ($orderedit[1]) - 1); 
                    $oldorder = $this->ordern->loadByIncrementId($invoice);
                   // print_r(get_class_methods($oldorder));
                    $transactionold = $this->getAuth($oldorder->getId());
                    if($transactionold == null) throw new \Exception("There is no transaction to reauth");
                    $referenceNumber = $transactionold->additional_information->raw_details_info->ReferenceNumber;
                    $amountold = $oldorder->getTotalDue();
                    if($amount > $amountold) $isReAuth = true;
                    else if($amount < $amountold){
                        $isReturn = true;
                        $amount = $amountold-$amount;
                        $ResponseSave = $transactionold->additional_information->raw_details_info;
                        $ResponseSave->Amount = $amountold-$amount;
                    }
                    else{
                        $ResponseSave = $transactionold->additional_information->raw_details_info;
                    }
                }

                 #Region
                 if(!$isEdit || $isReAuth || $isReturn){
                    $ch = curl_init($thisss->getConfigData('url')."/?app=genericcontroller&action=siteVerify");
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt ($ch, CURLOPT_POST, 1);
   
                    $postSend = "secretkey=".$thisss->getConfigData('secretkey');
                    $postSend .= "&merchant=".$thisss->getConfigData('merchantid');
                    $postSend .= "&amount=".$amount;
                    $postSend .= "&invoicenumber=".$order->getOrderIncrementId();

                    if(!$isEdit){
                        $postSend .= "&address=".$billingAddressInfo->getStreetLine1();//
                        $postSend .= "&state=".$billingAddressInfo->getRegionCode();//
                        $postSend .= "&city=".$billingAddressInfo->getCity();
                        $postSend .= "&type=Auth";
                        $postSend .= "&type3d=FunctionAuto";
                        $postSend .= "&cardinalreturn=returnCardinalMag";
                        $postSend .= "&zipcode=".$billingAddressInfo->getPostcode();
                        $postSend .= "&tokenid=".$RecurringSaleTokenId;
                        if(!empty($billingAddressInfo->getCustomerId())) $postSend .= "&customercode=".$billingAddressInfo->getCustomerId();
                        $postSend .= "&email=".$billingAddressInfo->getEmail();
                    }else  $postSend .= "&referencenumber=".$referenceNumber;
                    $postSend .= "&ip=$ip";
                 
                    curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);
   
                    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
   
                    $ResponseLocal = curl_exec($ch);
   
                    $error = curl_error($ch);
                    curl_close ($ch);
                    if(!empty($error))  {
                        throw new \Exception($error);
                    }
   
                    $ResponseLocal = json_decode($ResponseLocal);
                    if($ResponseLocal->Result != 0) {
                        throw new \Exception($ResponseLocal->Message);
                    }
                    $endpoint = (strpos(strtoupper($RecurringSaleTokenId), 'CRYPTO') !== false) ?  "UseCrypto" : ((!$isEdit) ? "UseToken" : (($isReturn) ?  "Refund" : "Reauth"));
   
                    $chProcess = curl_init($thisss->getConfigData('url')."/api/$endpoint/");
   
                    curl_setopt($chProcess, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt ($chProcess, CURLOPT_POST, 1);
   
                    $postProces = "verifyingpost=".$ResponseLocal->Data;
                    if(!$isEdit) $postProces .= "&tokenid=".$RecurringSaleTokenId;
                    else $postProces .= "&referencenumber=".$referenceNumber;
                    curl_setopt ($chProcess, CURLOPT_POSTFIELDS, $postProces);
   
                    curl_setopt($chProcess,CURLOPT_RETURNTRANSFER, true);
                    
                    $ResponseLocal = curl_exec($chProcess);
   
                    $error = curl_error($chProcess);
   
                    curl_close ($chProcess);
                    if(!empty($error))  {
                        throw new \Exception($error);
                    }
   
                    $ResponseLocal = json_decode($ResponseLocal);
                    if($ResponseLocal->Result !== 0 && $ResponseLocal->Result !== 21){
                        throw new \Exception($ResponseLocal->Message);
                    }

                    if(!$isReturn) $ResponseSave = $ResponseLocal;
                 }
                 #EndRegion
             } catch (\Exception $ex) {
                 $ResponseSave = new \stdClass();
                 $ResponseSave->Message = $ex->getMessage();
                 $ResponseSave->Result = -1;
             }

           if($ResponseSave->Result === -1 || $ResponseSave->Result === 21){
                if($isEdit){
                    throw new \InvalidArgumentException($ResponseSave->Message);
                }else{
                    if($ResponseSave->Result === -1){
                        throw new \Magento\Framework\Exception\LocalizedException(__($ResponseSave->Message));
                    }else if ($ResponseSave->Result === 21){
                        throw new \Magento\Framework\Exception\LocalizedException(__(json_encode($ResponseSave)));
                    }
                }
            }
                     
            if($ResponseSave->Result === 0){
                $inarray = array("Result","AutorizationNumber","ReferenceNumber","TraceNumber","Amount","CardType",
                    "Message","RecurringTokenId","InvoiceNumber","OriginalAmount");
                foreach ($ResponseSave as $key => $value) {
                    if(in_array($key, $inarray) && $key !="extension_attributes") $payment->setAdditionalInformation($key, $value);
                }
            }
        }
        
        return [
            self::FORCE_RESULT =>  $ResponseSave->Result == 0 ? 1 : 0
        ];
    }
}
