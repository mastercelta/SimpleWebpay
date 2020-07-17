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
                    'url' => $this->method->getConfigData('url'),
                    'urlprocess' => $this->method->getConfigData('url_view'),
                    'iscvv' => ($this->method->getConfigData('iscvv') === "1")? "true" : "false",
                    'istoken19' => ($this->method->getConfigData('usetoken') === "token19")? "true" : "false",
                    'usetoken' => ($this->method->getConfigData('usetoken') === "1")? "true" : "false",
                    'urlsave' =>  $this->urlBuilder->getUrl("simplewebpay/index/index"),
                    'urlsession' =>  $this->urlBuilder->getUrl("simplewebpay/index/SessionData"),
                    'url3d' => $this->urlBuilder->getUrl("simplewebpay/index/process"),
                    'urlimage' => $this->getImage("Cenpos_SimpleWebpay::images/loader.gif")
                ]
            ]
        ];
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
