<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Block\Form;

class ReOrderAuth extends \Magento\Payment\Block\Form\Cc
{
    /**
     * Checkmo template
     *
     * @var string
     */
    protected $_template = 'Cenpos_SimpleWebpay::form/swppayment.phtml';
}
