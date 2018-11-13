<?php
namespace Cenpos\SimpleWebpay\Block\Customer;
class ManageToken extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry;
    protected $_customerSession2;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_paymentMethod = $paymentMethod;
        $this->_customerSession2 = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    public function getcardmanager()
    {
        // will return 'bar'
        $ResponseSave = new \stdClass();
        try{
            $ip = $_SERVER["REMOTE_ADDR"];
            if($this->_paymentMethod->getConfigData('url') == null || $this->_paymentMethod->getConfigData('url') == "" ){
                throw new \Exception("The url credit card must be configured");
            }

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $cartObj = $objectManager->get('\Magento\Checkout\Model\Cart');

            $billingAddressInfo = $cartObj->getQuote()->getBillingAddress();

            $dataAddress = $billingAddressInfo->getData();

            $Street = "";
            if($dataAddress != null && array_key_exists("street", $dataAddress)){
                if (strpos($dataAddress['street'], "\n") !== FALSE) {
                    $Street = str_replace("\n", " ", $dataAddress['street']);
                }
            }else $Street = "";

            $this->_coreRegistry->register('urloption', $this->_paymentMethod->getConfigData('url'));
            $ch = curl_init($this->_paymentMethod->getConfigData('url')."/?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);
            $email = "";
            $customer = $this->_customerSession2->getCustomer();
            
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
        
        return $ResponseSave;
    }
    public function geturlprocess()
    {
        // will return 'bar'

        return  $this->_paymentMethod->getConfigData('url_view');
    }
}