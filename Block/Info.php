<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cenpos\SimpleWebpay\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Cenpos\SimpleWebpay\Gateway\Response\FraudHandler;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case FraudHandler::FRAUD_MSG_LIST:
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        $info = $this->getInfo();
       
        if ($this->_appState->getAreaCode() === \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
            && $info->getAdditionalInformation()
        ) {
            $inarray = array("AutorizationNumber","ReferenceNumber","TraceNumber","Amount","CardType");
            foreach ($info->getAdditionalInformation() as $field => $value) {
                if(in_array($field, $inarray)){
                     $beautifiedFieldName = ucwords(trim(preg_replace('/(?<=\\w)(?=[A-Z])/', " $1", $field)));
                    $data[__($beautifiedFieldName)->getText()] = $value;
                }
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
