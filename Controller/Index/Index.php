<?php 
namespace Cenpos\SimpleWebpay\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $ResponseSave = new \stdClass();
        try{
            $ip = $_SERVER["REMOTE_ADDR"];
            if($this->_paymentMethod->getConfigData('url') == null || $this->_paymentMethod->getConfigData('url') == "" ){
                throw new \Exception("The url credit card must be configured");
            }
            $RecurringSaleTokenId = $_POST["RecurringSaleTokenId"];
            if(empty($RecurringSaleTokenId)) {
                throw new Exception("the data crypto cant be empty");
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