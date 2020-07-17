<?php 
namespace Cenpos\SimpleWebpay\Model\Category;

class TokenOption implements \Magento\Framework\Option\ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => '0', 'label' => __('No')],
    ['value' => '1', 'label' => __('Standard')],
    ['value' => 'token19', 'label' => __('19 Digit')]
  ];
 }
}
?>