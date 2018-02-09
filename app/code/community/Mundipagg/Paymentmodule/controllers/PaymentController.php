<?php

class Mundipagg_Paymentmodule_PaymentController extends Mundipagg_Paymentmodule_Controller_Payment
{
    public function processPaymentAction()
    {
        $this->standard = Mage::getModel('paymentmodule/standard');
        $this->orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $this->order = $this->standard->getOrderByOrderId($this->orderId);

        $paymentMethod = $this->order->getPayment()->getMethodInstance()->getCode();
        $controller = ucfirst(end(explode("_",$paymentMethod)));
        $controller = Mage::getModel('paymentmodule/paymentmethods/' . $controller);
        $controller->processPayment();
        /*
        $apiOrder = Mage::getModel('paymentmodule/api_order');

        $paymentInfo = new Varien_Object();

        $paymentInfo->setItemsInfo($this->getItemsInformation());
        $paymentInfo->setCustomerInfo($this->getCustomerInformation());
        $paymentInfo->setPaymentInfo($this->getPaymentInformation());
        $paymentInfo->setShippingInfo($this->getShippingInformation());
        $paymentInfo->setMetaInfo(Mage::helper('paymentmodule/data')->getMetaData());

        try {
            $response = $apiOrder->createBoletoPayment($paymentInfo);

            if (gettype($response) !== 'object' || get_class($response) != GetOrderResponse::class) {
                throw new Exception("Response must be object.");
            }

            $this->handleOrderResponse($response, true);
        }catch(Exception $e) {
            $helperLog = Mage::helper('paymentmodule/log');
            $helperLog->error("Exception: " . $e->getMessage());
            $helperLog->error(json_encode($response,JSON_PRETTY_PRINT));
        }*/
    }
}
