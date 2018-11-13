<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
class AfterSaveOrderObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public $state;

    public function __construct(\Magento\Framework\App\State $statel)
    {
       $this->state = $statel;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            $order = $observer->getData("order_ids");


            $order2 = $observer->getData("order");
            $id = ($order != null) ? $order[0] : null;
            
            $codecurrent= $this->state->getAreaCode();

            if($id != null && $order != null){
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('\Magento\Sales\Model\Order')->load($id);
                $orderState = Order::STATE_PENDING_PAYMENT;
                $order->setState($orderState)->setStatus("pending");
                $order->setActionFlag(Order::ACTION_FLAG_EDIT, true);
                $order->save();
            }
    }
}
