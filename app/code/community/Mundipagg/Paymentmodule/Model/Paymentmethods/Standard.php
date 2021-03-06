<?php

use MundiAPILib\Models\GetOrderResponse;

class Mundipagg_Paymentmodule_Model_Paymentmethods_Standard extends Mundipagg_Paymentmodule_Model_Standard
{
    /**
     * Gather boleto transaction information and try to create
     * payment using sdk api wrapper.
     */
    public function processPayment($method)
    {
        $apiOrder = Mage::getModel('paymentmodule/api_order');

        $paymentInfo = new Varien_Object();

        $paymentInfo->setItemsInfo($this->getItemsInformation());
        $paymentInfo->setCustomerInfo($this->getCustomerInformation());
        $paymentInfo->setPaymentInfo($method);
        $paymentInfo->setShippingInfo($this->getShippingInformation());
        $paymentInfo->setMetaInfo(Mage::helper('paymentmodule/data')->getMetaData());

        $response = $apiOrder->createPayment($paymentInfo);

        $this->handleOrderResponse($response);
    }

    /**
     * Take the result from processPaymentTransaction, add the histories and, if $redirect is true,
     * redirect customer to success page.
     *
     * @param $response
     * @param bool $redirect
     */
    protected function handleOrderResponse($response)
    {
        $redirectTo = Mage::helper('paymentmodule/redirect');

        if (
            gettype($response) !== 'object' ||
            get_class($response) != GetOrderResponse::class
        ) {
            $this->handleOrderFailure($response);
            $redirectTo->orderFailure();
            return;
        }

        $this->handleOrderSuccess($response);
        $redirectTo->orderSuccess();
    }

    private function handleOrderFailure($response)
    {
        $helperLog = Mage::helper('paymentmodule/log');
        $orderId = $this->lastRealOrderId;
        $helperLog->error("Invalid response for order #$orderId: ");
        $helperLog->error(json_encode($response,JSON_PRETTY_PRINT));
    }

    private function handleOrderSuccess($response)
    {
        $chargeHelper = Mage::helper('paymentmodule/charge');

        $savedCreditCard = Mage::helper('paymentmodule/savedcreditcard');
        $savedCreditCard->saveCards($response);

        //get additional information about boleto payments
        $standard = Mage::getModel('paymentmodule/standard');
        $orderId = $response->code;
        $additionalInformation = $standard->getAdditionalInformationForOrder($orderId);
        $paymentMethod = $additionalInformation['mundipagg_payment_method'];
        $paymentInfo = $additionalInformation[$paymentMethod];
        $boletosInfo = [];

        if (isset($paymentInfo['boleto'])) {
            $boletosInfo = $paymentInfo['boleto'];
        }

        /**
         * @todo fix charge handle
         */
        //processing charges;
        foreach ($response->charges as $chargeIndex => $charge) {
            $charge->code = $response->code;

            $chargeHelper->updateStatus($charge, $charge->status);

            //search for boleto link
            if ($charge->paymentMethod === 'boleto') {
                $boletoUrl = $charge->lastTransaction->url;
                //add to additional information boleto link.
                foreach($boletosInfo as &$boletoInfo){
                    if(!isset($boletoInfo['url'])) {
                        $boletoInfo['url'] =  $boletoUrl;
                        break;
                    }
                }
            }
        }

        if(count($boletosInfo) > 0) {
            //update additional information with boleto links.
            $additionalInformation[$paymentMethod]['boleto'] = $boletosInfo;
            $payment = $standard->getOrderByIncrementOrderId($orderId)->getPayment();
            $payment->setAdditionalInformation($paymentMethod, $additionalInformation[$paymentMethod]);
            $payment->save();
        }
    }

    /**
     * Gather information about customer
     *
     * @return Varien_Object
     * @throws Varien_Exception
     */
    protected function getCustomerInformation()
    {
        $standard = Mage::getModel('paymentmodule/standard');
        $customerSession = $standard->getCustomerSession();

        $customer = $customerSession->getCustomer();
        $customerId = $customer->getId();

        $information = new Varien_Object();

        $information->setName($customer->getName());
        $information->setEmail($customer->getEmail());
        $information->setDocument($customer->getDocument());
        // @todo where does it should come from?
        $information->setType('individual');
        $information->setAddress($this->getCustomerAddressInformation());
        $information->setMetadata(null);
        $information->setPhones($this->getCustomerPhonesInformation());
        $information->setCode($customerId);

        return $information;
    }

    /**
     * Gather information about customer's address
     *
     * @return Varien_Object
     * @throws Varien_Exception
     */
    protected function getCustomerAddressInformation()
    {
        return Mage::helper('paymentmodule/address')->getCustomerAddressInformation();
    }

    protected function getShippingInformation($order = null)
    {
        if (!$order) {
            $standard = Mage::getModel('paymentmodule/standard');
            $checkoutSession = $standard->getCheckoutSession();
            $orderId = $checkoutSession->getLastOrderId();
            $order = $standard->getOrderByOrderId($orderId);
        }

        $monetaryHelper = Mage::helper('paymentmodule/monetary');
        $shipping = new Varien_Object();

        $shipping->setAmount($monetaryHelper->toCents($order->getShippingAmount()));
        $shipping->setDescription($order->getShippingDescription());
        $shipping->setAddress($this->getShippingAddressInformation($order));

        return $shipping;
    }

    protected function getShippingAddressInformation($order = null) {
        return Mage::helper('paymentmodule/address')
            ->getShippingAddressInformation($order);
    }

    /**
     * Gather information about customer's phones
     *
     * @return Varien_Object
     */
    protected function getCustomerPhonesInformation()
    {
        $phones = new Varien_Object();

        // @todo it must not be hard coded
        $phones->setCountryCode('55');
        $phones->setNumber('9999999999');
        $phones->setAreacode('21');

        return $phones;
    }

    /**
     * Provide ordered items information
     * @return array
     */
    protected function getItemsInformation()
    {
        $items = [];

        $standard = Mage::getModel('paymentmodule/standard');
        $checkoutSession = $standard->getCheckoutSession();
        $orderId = $checkoutSession->getLastRealOrderId();

        $order = $standard->getOrderByIncrementOrderId($orderId);

        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId() === null) {
                $itemInfo = [];

                $itemInfo['amount'] = round($item->getPrice() * 100);
                $itemInfo['quantity'] = (int) $item->getQtyOrdered();
                $itemInfo['description'] = 'item description';

                $items[] = $itemInfo;
            }
        }

        return $items;
    }
}
