<?php
namespace Cenpos\SimpleWebpay\Controller\Customer;

class Index extends \Magento\Framework\App\Action\Action {
    
    protected $_customerSession;
    protected $resultPageFactory;
    protected $_paymentMethod;
    protected $_checkoutSession;
    protected $checkout;
    protected $cartManagement;
    protected $guestcartManagement;
    protected $orderRepository;
    protected $_scopeConfig;
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Cenpos\SimpleWebpay\Model\Ui\ConfigProvider $paymentMethod,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute() {

        $this->_view->loadLayout();

        $this->_view->renderLayout();
    }
}