<?php

use MundiAPILib\Models\GetOrderResponse;

class Mundipagg_Paymentmodule_BoletoccController extends Mundipagg_Paymentmodule_Controller_Payment
{
    /**
     * Gather boleto transaction information and try to create
     * payment using sdk api wrapper.
     */
    public function processPaymentAction()
    {
        $apiOrder = Mage::getModel('paymentmodule/api_order');

        $paymentInfo = new Varien_Object();

        $paymentInfo->setItemsInfo($this->getItemsInformation());
        $paymentInfo->setCustomerInfo($this->getCustomerInformation());
        $paymentInfo->setPaymentInfo($this->getPaymentInformation());
        $paymentInfo->setShippingInfo($this->getShippingInformation());
        $paymentInfo->setMetaInfo(Mage::helper('paymentmodule/data')->getMetaData());

        try {
            $response = $apiOrder->createBoletoCreditcardPayment($paymentInfo);

            if (gettype($response) !== 'object' || get_class($response) != GetOrderResponse::class) {
                throw new Exception("Response must be object.");
            }

            $this->handleOrderResponse($response, true);
        } catch(Exception $e) {
            $helperLog = Mage::helper('paymentmodule/log');
            $helperLog->error("Exception: " . $e->getMessage());
            $helperLog->error(json_encode($response,JSON_PRETTY_PRINT));
        }
    }

    private function initPaymentInfoSources() {
        $this->standard = Mage::getModel('paymentmodule/standard');
        $checkoutSession = $this->standard->getCheckoutSession();
        $this->orderId = $checkoutSession->getLastRealOrderId();
        $this->additionalInformation = $this->standard->getAdditionalInformationForOrder($this->orderId);
        $this->boletoCcConfig = Mage::getModel('paymentmodule/config_boletocc');

        $this->orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        $this->order = $this->standard->getOrderByOrderId($this->orderId);
        $this->antifraudConfig = Mage::getModel('paymentmodule/config_antifraud');
    }

    /**
     * Gather information about payment
     *
     * @return Varien_Object
     */
    private function getPaymentInformation()
    {
        $this->initPaymentInfoSources();

        $payment = new Varien_Object();
        $this->getBoletoPaymentInformation($payment);
        $this->getCreditcardPaymentInformation($payment);

        $test = $this->order->getPayment()->getMethodInstance()->getCode();

        return $payment;
    }


    private function getBoletoPaymentInformation(Varien_Object &$payment)
    {
        $grandTotal = $this->order->getGrandTotal();

        $payment->setPaymentMethod('boletocc');
        $payment->setAmount($grandTotal);
        $payment->setBank($this->boletoCcConfig->getBank());
        $payment->setInstructions($this->boletoCcConfig->getInstructions());
        $payment->setDueAt($this->boletoCcConfig->getDueAt());
        $payment->setBoletoValue($this->additionalInformation['mundipagg_payment_module_boleto_value']);
    }

    private function getCreditcardPaymentInformation(Varien_Object &$payment)
    {
        $interest = $this->additionalInformation['mundipagg_payment_module_interest'];
        $baseGrandTotal = $this->additionalInformation['mundipagg_payment_module_base_grand_total'];

        $payment->setInstallmentNumber($this->additionalInformation['mundipagg_payment_module_installments']);
        $payment->setInvoiceName($this->boletoCcConfig->getInvoiceName());
        $payment->setOperationType($this->boletoCcConfig->getOperationTypeFlag());
        $payment->setPaymentToken($this->additionalInformation['mundipagg_payment_module_token']);
        $payment->setHolderName($this->additionalInformation['mundipagg_payment_module_holder_name']);
        $payment->setBaseGrandTotal([$baseGrandTotal]);
        $payment->setInterest([$interest]);
        $payment->setCurrency('BRL'); // @todo get this from store config
        $payment->setSendToAntiFraud($this->antifraudConfig->shouldApplyAntifraud($baseGrandTotal));
        $payment->setCreditcardValue($this->additionalInformation['mundipagg_payment_module_creditcard_value']);
    }
}
