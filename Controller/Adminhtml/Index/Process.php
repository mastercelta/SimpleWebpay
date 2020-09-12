<?php
namespace Cenpos\SimpleWebpay\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Process extends Action
{
	/**
	 * @var \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider
	 */
    protected $_paymentMethod;

	/**
	 * @var \Magento\Quote\Model\QuoteManagement
	 */
    protected $_quoteManagement;

	/**
	 * @var \Magento\Backend\Model\Session\Quote
	 */
    protected $_quoteSession;

	/**
	 * Process constructor.
	 * @param Context $context
	 * @param \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod
	 * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
	 * @param \Magento\Backend\Model\Session\Quote $quoteSession
	 */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
        parent::__construct($context);
        $this->_paymentMethod = $paymentMethod;
        $this->_quoteManagement = $quoteManagement;
        $this->_quoteSession = $quoteSession;
    }

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
	 */
    public function execute()
    {
        $ResponseSave = new \stdClass();
        try{
            error_reporting(0);
            $quote = $this->_quoteSession->getQuote();
            $ResponseSave = (object) $_POST;
            $quote->reserveOrderId();
            $payment = $quote->getPayment();
            $quote->getPayment()->setMethod('swppayment');
            
            if($ResponseSave->Result === "0"){
                $inarray = array("Result","AutorizationNumber","ReferenceNumber","TraceNumber","Amount","CardType",
                    "Message","RecurringTokenId","InvoiceNumber","OriginalAmount");
                foreach ($ResponseSave as $key => $value) {
                    if(in_array($key, $inarray) && $key !="extension_attributes") $payment->setAdditionalInformation($key, $value);
                }
                
                $payment->setTransactionId($_POST["ReferenceNumber"]);
                $payment->save();
                $quote->save();
                $this->_quoteManagement->submit($quote);
            }
            
            if($ResponseSave->Result != "0") {
                throw new \Exception($ResponseSave->Message);
            }
            
        } catch (\Exception $ex) {
            $ResponseSave->Message = $ex->getMessage();
            $ResponseSave->Result = -1;
        }
        
        echo json_encode($ResponseSave);
    }
}