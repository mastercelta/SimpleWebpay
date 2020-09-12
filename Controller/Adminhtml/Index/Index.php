<?php
namespace Cenpos\SimpleWebpay\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Index extends Action
{
	/**
	 * @var \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider
	 */
    protected $_paymentMethod;

	/**
	 * @var \Magento\Backend\Model\Session\Quote
	 */
    protected $_quoteSession;

	/**
	 * Index constructor.
	 * @param Context $context
	 * @param \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod
	 * @param \Magento\Backend\Model\Session\Quote $quoteSession
	 */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
        parent::__construct($context);
        $this->_paymentMethod = $paymentMethod;
        $this->_quoteSession = $quoteSession;
    }

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
	 */
    public function execute()
    {
        $ResponseSave = new \stdClass();
        try{
            $ip = $_SERVER["REMOTE_ADDR"];
            if(empty($this->_paymentMethod->getConfigData('url'))){
                throw new \Exception("The url credit card must be configured");
            }
            $RecurringSaleTokenId = $this->getRequest()->getParam('RecurringSaleTokenId');
            if(empty($RecurringSaleTokenId)) {
                throw new Exception("the data crypto cant be empty");
            }

            $quote = $this->_quoteSession->getQuote();
	        $billingAddressInfo = $quote->getBillingAddress();
            $dataAddress = $billingAddressInfo->getData();

            if($dataAddress != null && array_key_exists("street", $dataAddress)){
                if (strpos($dataAddress['street'], "\n") !== FALSE) {
                    $Street = str_replace("\n", " ", $dataAddress['street']);
                }
                else{
                    $Street = $dataAddress['street'];
                } 
            }else {
                $Street = "";
            } 


            $ch = curl_init($this->_paymentMethod->getConfigData('url')."/?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);

            $postSend = "secretkey=".$this->_paymentMethod->getConfigData('secretkey');
            $postSend .= "&merchant=".$this->_paymentMethod->getConfigData('merchantid');
            $postSend .= "&address=".$Street;
            $postSend .= "&state=".$dataAddress["region"];
            $postSend .= "&city=".$dataAddress["city"];
            $postSend .= "&zipcode=".$dataAddress["postcode"];
            $postSend .= "&tokenid=".$RecurringSaleTokenId;
            if(!empty($dataAddress["customer_id"])){
                $postSend .= "&customercode=".$dataAddress["customer_id"];
            }
            $postSend .= "&email=".$dataAddress["email"];
            $postSend .= "&ip=$ip";
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);

            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            $ResponseSave = curl_exec($ch);

            $error = curl_error($ch);
            curl_close ($ch);
            if(!empty($error))  {
                throw new Exception($error);
            }

            $ResponseSave = json_decode($ResponseSave);
            if($ResponseSave->Result != 0) {
                throw new \Exception($ResponseSave->Message);
            }
              
            $chProcess = curl_init($this->_paymentMethod->getConfigData('url')."/api/ConvertCrypto/");

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
            if($ResponseSave->Result != 0) {
                throw new \Exception($ResponseSave->Message);
            }
        } catch (\Exception $ex) {
            $ResponseSave->Message = $ex->getMessage();
            $ResponseSave->Result = -1;
        }
        
        echo json_encode($ResponseSave);
    }
}