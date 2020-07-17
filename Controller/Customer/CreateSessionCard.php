<?php
namespace Cenpos\SimpleWebpay\Controller\Customer;

class CreateSessionCard extends \Magento\Framework\App\Action\Action {
    
    protected $_customerSession;
    protected $resultPageFactory;
    protected $_paymentMethod;
    protected $_checkoutSession;
    protected $checkout;
    protected $cartManagement;
    protected $guestcartManagement;
    protected $orderRepository;
    protected $_scopeConfig;
    protected $_coreRegistry;
    protected $cartObj;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Checkout\Model\Cart $cart
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->cartObj = $cart;
    }

    public function execute() {
        $ResponseSave = new \stdClass();
        $ResponseSave->Result = -1;
        $ResponseSave->Message = "Incomplete";
        $ResponseSave->Data = "";
        try{
            $ip = $_SERVER["REMOTE_ADDR"];
            if($this->_paymentMethod->getConfigData('url') == null || $this->_paymentMethod->getConfigData('url') == "" ){
                throw new \Exception("The url credit card must be configured");
            }

            $billingAddressInfo = $this->cartObj->getQuote()->getBillingAddress();

            $dataAddress = $billingAddressInfo->getData();

            $Street = "";
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

            $this->_coreRegistry->register('urloption', $this->_paymentMethod->getConfigData('url'));
         
            $ch = curl_init($this->_paymentMethod->getConfigData('url')."/?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);
            $email = "";
            $customer = $this->_customerSession->getCustomer();
            
            $postSend = "secretkey=".$this->_paymentMethod->getConfigData('secretkey');
            $postSend .= "&merchant=".$this->_paymentMethod->getConfigData('merchantid');
            if($customer){
                if($customer->getData()["email"]) $postSend .= "&email=".$customer->getData()["email"];
                if($customer->getData()["entity_id"]) $postSend .= "&customercode=".$customer->getData()["entity_id"];
            }
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
           
        } catch (\Exception $ex) {
            $ResponseSave->Message = $ex->getMessage();
            $ResponseSave->Result = -1;
        }
        
        $ResponseSave->Url = $this->_paymentMethod->getConfigData('url_view');
        
        echo json_encode($ResponseSave);
      
    }
}