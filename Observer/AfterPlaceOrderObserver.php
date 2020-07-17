<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
class AfterPlaceOrderObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */

    public $state;
    public $method;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\App\State $statel)
    {
       $this->state = $statel;
       $this->method = $paymentHelper->getMethodInstance("swppayment");
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData("order_ids");
        $order2 = $observer->getData("order");
        $id = ($order != null) ? $order[0] : null;
        $paymentaction = $this->method->getConfigData('payment_action');
        $codecurrent= $this->state->getAreaCode();

        if($id != null){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order =  $objectManager->create('\Magento\Sales\Model\Order')->load($id);
            $orderState = ($paymentaction == "authorize_capture") ? Order::STATE_PROCESSING  : Order::STATE_PENDING_PAYMENT;
            $order->setState($orderState)->setStatus(($paymentaction == "authorize_capture") ? "processing" : "pending");
            $order->setActionFlag(Order::ACTION_FLAG_EDIT, true);
            $order->save();
        }
    }
}