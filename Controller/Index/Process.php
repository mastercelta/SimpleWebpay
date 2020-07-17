<?php 
namespace Cenpos\SimpleWebpay\Controller\Index;

class Process extends \Magento\Framework\App\Action\Action
{
    protected $_customerSession;
    protected $resultPageFactory;
    protected $_paymentMethod;
    protected $_checkoutSession;
    protected $checkout;
    protected $cartManagement;
    protected $guestcartManagement;
    protected $orderRepository;
    protected $_scopeConfig;
    protected $_orderFactory;
    protected $sadasdasd;
    protected $_quoteManagement;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
            
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->_quoteManagement = $quoteManagement;
        $this->response = $response;
    }

    public function execute()
    {
        $ResponseSave = new \stdClass();
        try{
            error_reporting(0);
            $quote = $this->_checkoutSession->getQuote();
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
                $order = $this->_quoteManagement->submit($quote);
           
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