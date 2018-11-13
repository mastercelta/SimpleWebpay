<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Cenpos\SimpleWebpay\Gateway\Http\Client\ClientMock;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'swppayment';
    const THROW_ERROR = "Error";
    const THROW_WARNING = "Warning";
    const THROW_SUCCESS = "Sucess";
    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\UrlInterface $url,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->method = $paymentHelper->getMethodInstance(self::CODE);
        $this->_messageManager = $messageManager;
        $this->response = $response;
        $this->urlBuilder = $url;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->request = $request;
        $this->assetRepo = $assetRepo;
    }
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ],
                    'verifyingPost' => $this->getConfigSimpleWebpay(),
                    'url' => $this->method->getConfigData('url'),
                    'urlprocess' => $this->method->getConfigData('url_view'),
                    'iscvv' => ($this->method->getConfigData('iscvv') === "1")? "true" : "false",
                    'usetoken' => ($this->method->getConfigData('usetoken') === "1")? "true" : "false",
                    'urlsave' =>  $this->urlBuilder->getUrl("simplewebpay/index/index"),
                    'url3d' => $this->urlBuilder->getUrl("simplewebpay/index/process"),
                    'urlimage' => $this->getImage("Cenpos_SimpleWebpay::images/loader.gif")
                ]
            ]
        ];
    }
    //Get fixed amount
    public function getConfigSimpleWebpay()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
    //            die();
        try{
              if($this->method->getConfigData('url') == null || $this->method->getConfigData('url') == "" ) $this->throwMessageCustom("The url credit card must be configured");

              $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

              $cartObj = $objectManager->get('\Magento\Checkout\Model\Cart');

              $billingAddressInfo = $cartObj->getQuote()->getBillingAddress();

              $dataAddress = $billingAddressInfo->getData();

              $Street = $dataAddress["street"];
              if (strpos($Street, "\n") !== FALSE) {
                  $Street = str_replace("\n", " ", $Street);
              }

          //    print_r(get_class_methods($this->_checkoutSession->getQuote()));

         //     print_r($this->_checkoutSession->getQuote()->getCustomerIsGuest());

          //    print_r($this->_checkoutSession->getStepData());

           //   die();
    //
              $ch = curl_init($this->method->getConfigData('url')."?app=genericcontroller&action=siteVerify");
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
              curl_setopt ($ch, CURLOPT_POST, 1);

              $postSend = "secretkey=".$this->method->getConfigData('secretkey');
              $postSend .= "&merchant=".$this->method->getConfigData('merchantid');
              $postSend .= "&address=".$Street;
              $postSend .= "&isrecaptcha=false";
              $postSend .= "&zipcode=".$dataAddress["postcode"];
              if ($this->_customerSession->isLoggedIn()) {
                  $customerData = $this->_customerSession->getCustomer();
                  $postSend .= "&customercode=".$customerData->getId();
              }
              $postSend .= "&email=".$dataAddress["email"];
              $postSend .= "&ip=$ip";
              curl_setopt ($ch, CURLOPT_POSTFIELDS, $postSend);

              curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);

              $response = curl_exec($ch);

              $error = curl_error($ch);
              curl_close ($ch);
              if(!empty($error))  {
                  $this->throwMessageCustom($error, "", self::THROW_ERROR);
              }

              $response = json_decode($response);
              if($response->Result != 0) {
                  $this->throwMessageCustom($response->Message, "", self::THROW_ERROR);
              }

        } catch (Exception $ex) {
            $this->throwMessageCustom($ex->getMessage());
        }
    //            
        return $response->Data;
    }
    
    public function getImage($name){
        $params = array('_secure' => $this->request->isSecure());
        return $this->assetRepo->getUrlWithParams($name, $params);
    }
    
    public function throwMessageCustom($Message, $url= "", $Type = self::THROW_ERROR){
        try{
            switch($Type){
                case self::THROW_ERROR:
                    $this->_messageManager->addError($Message);
                    break;
                case self::THROW_SUCCESS:
                    $this->_messageManager->addSuccess($Message);
                break;
                
                case self::THROW_WARNING:
                    $this->_messageManager->addWarning($Message);
                break;
            }
            $url = $this->urlBuilder->getUrl($url);
            $this->response->setRedirect($url);
        } catch (Exception $ex) {
            $this->_messageManager->addError($ex->getMessage());
            $url = $this->urlBuilder->getUrl('');
            $this->response->setRedirect($url);
        }
    }
    
    public function getConfigData($name){
        return $this->method->getConfigData($name);
    }
}
