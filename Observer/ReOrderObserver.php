<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
class ReOrderObserver implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */

    public function __construct(
    ) {
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData("order_ids");

        if($order != null){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\Order')->load($order[0]);
            $orderState = Order::STATE_PENDING_PAYMENT;
            $order->setState("pending")->setStatus("new");
            $order->save();
        }

    }
}