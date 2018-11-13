<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

abstract class CommonRequest implements BuilderInterface
{
   
    protected function getAuth($orderid, $type = \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH){
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Magento\Sales\Model\Order\Payment\Transaction')->getCollection();
        
        $data = $model->addAttributeToFilter('order_id', array('eq' => $orderid))
                ->addAttributeToFilter('txn_type', array('eq' => $type));

        $data = json_decode(json_encode($data->toArray()), FALSE);
        
        $transaction = null;
       // $this->config->getValue('payment_action');
        foreach ($data->items as $key => $item) {
            if ($item->is_closed == 0) {
                $transaction = $item;
            }
        }

        return $transaction;
    }
}
 
 