<?php 
namespace Cenpos\SimpleWebpay\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class SessionData extends Action
{
	/**
	 * @var \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider
	 */
    protected $_paymentMethod;

	/**
	 * @var \Magento\Customer\Api\CustomerRepositoryInterface
	 */
    protected $_customerRepository;

	/**
	 * SessionData constructor.
	 * @param Context $context
	 * @param \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod
	 * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
	 */
    public function __construct(
        Context $context,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->_paymentMethod = $paymentMethod;
        $this->_customerRepository = $customerRepository;
    }

	/**
	 * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
	 */
	public function execute()
    {
        $ResponseSave = new \stdClass();
        try {
	        $ip = $_SERVER["REMOTE_ADDR"];
	        if (empty($this->_paymentMethod->getConfigData('url'))) {
		        $this->throwMessageCustom("The url credit card must be configured");
            }

            $ch = curl_init($this->_paymentMethod->getConfigData('url')."?app=genericcontroller&action=siteVerify");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_POST, 1);

            $postSend = "secretkey=" . $this->_paymentMethod->getConfigData('secretkey');
            $postSend .= "&merchant=" . $this->_paymentMethod->getConfigData('merchantid');
            $postSend .= "&address=". $this->getRequest()->getParam('address');
            $postSend .= "&isrecaptcha=false";
	        $postSend .= "&zipcode=" . $this->getRequest()->getParam('zipcode');

	        $customerEmail = $this->getRequest()->getParam('email');

	        if (!empty($customerEmail)) {
	            try {
                    $customer = $this->_customerRepository->get($customerEmail);
                    if($customer) {
                        $postSend .= "&customercode=" . $customer->getId();
                        $postSend .= "&email=" . $customerEmail;
                    }
                } catch (\Exception $e) {
                    //No worries if we didn't find the customer.
                }
	        }

            $postSend .= "&ip=$ip";
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);

            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

            $ResponseSave = curl_exec($ch);

            $error = curl_error($ch);
            curl_close($ch);
            if(!empty($error))  {
                throw new \Exception($error);
            }
        
            $ResponseSave = json_decode($ResponseSave);

            if($ResponseSave->Result != 0) {
                throw new \Exception($ResponseSave->Message);
            }
        } catch (\Exception $ex) {
            if (!isset($ResponseSave)){
                $ResponseSave = new \stdClass();
            }
            $ResponseSave->Message = $ex->getMessage();
            $ResponseSave->Result = -1;
        }
        
        echo json_encode($ResponseSave);
    }
}