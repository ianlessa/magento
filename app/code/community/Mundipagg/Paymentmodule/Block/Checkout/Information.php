<?php

class Mundipagg_Paymentmodule_Block_Checkout_Information extends Mage_Payment_Block_Form
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentmodule/checkout/information.phtml');

        $this->initAdditionalInformation();
    }

    protected function initAdditionalInformation() {
        $orderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $additionalInformation = $order->getPayment()->getAdditionalInformation();

        $paymentMethod = $additionalInformation['mundipagg_payment_method'];
        $paymentInfo = $additionalInformation[$paymentMethod];

        $this->setPaymentInformation($paymentInfo);
    }

    public function getBilletData() {
        $paymentInformation = $this->getPaymentInformation();
        $billetData = [];
        if (isset($paymentInformation['boleto'])) {
            $billetData = $paymentInformation['boleto'];
        }

        return $billetData;
    }
}