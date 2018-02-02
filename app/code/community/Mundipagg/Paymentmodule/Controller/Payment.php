<?php

use MundiAPILib\Models\GetOrderResponse;

class Mundipagg_Paymentmodule_Controller_Payment extends Mage_Core_Controller_Front_Action
{
    /**
     * Take the result from processPaymentTransaction, add the histories and, if $redirect is true,
     * redirect customer to success page.
     *
     * @param $response
     * @param bool $redirect
     * @throws Varien_Exception
     */
    protected function handleOrderResponse($response, $redirect = false)
    {
        $moduleModelOrder = Mage::getModel('paymentmodule/core_order');
        $moduleModelCharge = Mage::getModel('paymentmodule/core_charge');

        foreach ($response->charges as $charge) {
            $charge->code = $response->code;
            $charge->order->id = $response->id;

            if ($charge->status == 'paid') {
                $charge->paid_amount = $charge->amount;
                $moduleModelCharge->paid($charge);
                $moduleModelOrder->paid($response);
            }
        }

        //@todo get boleto and the rest of success page data
        $data = [];

        if ($response->status === 'failed') {
            $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
            return;
        }
        $this->_redirect('checkout/onepage/success', ['_secure' => true], $data);
    }

    /**
     * Gather information about customer
     *
     * @return Varien_Object
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
        $information->setDocument(null);
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
     */
    protected function getCustomerAddressInformation()
    {
        $standard = Mage::getModel('paymentmodule/standard');
        $checkoutSession = $standard->getCheckoutSession();

        $orderId = $checkoutSession->getLastOrderId();
        $order = $standard->getOrderByOrderId($orderId);
        $billingAddress = $order->getBillingAddress();
        $regionId = $billingAddress->getRegionId();
        $address = new Varien_Object();

        // @fixme I'm using this getStreet()[0] here but maybe there's a better way...
        $address->setStreet($billingAddress->getStreet()[0]);
        $address->setNumber('number');
        $address->setZipCode($billingAddress->getPostcode());
        $address->setNeighborhood('neighborhood');
        $address->setCity($billingAddress->getCity());
        $address->setState($this->getStateByRegionId($regionId));
        $address->setCountry($billingAddress->getCountryId());
        $address->setComplement('complement');
        $address->setMetadata(null);
        return $address;
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
        // @todo This method is like self::getCustomerAddressInformation. Refact it to one method.
        if (!$order) {
            $standard = Mage::getModel('paymentmodule/standard');
            $checkoutSession = $standard->getCheckoutSession();
            $orderId = $checkoutSession->getLastOrderId();
            $order = $standard->getOrderByOrderId($orderId);
        }

        $shippingAddress = $order->getShippingAddress();
        $regionId = $shippingAddress->getRegionId();
        $address = new Varien_Object();

        // @fixme I'm using this getStreet()[0] here but maybe there's a better way...
        $address->setStreet($shippingAddress->getStreet()[0]);
        $address->setNumber('number');
        $address->setZipCode($shippingAddress->getPostcode());
        $address->setNeighborhood('neighborhood');
        $address->setCity($shippingAddress->getCity());
        $address->setState($this->getStateByRegionId($regionId));
        $address->setCountry($shippingAddress->getCountryId());
        $address->setComplement('complement');
        $address->setMetadata(null);

        return $address;
    }

    /**
     * Return state code
     * @example $this->getStateByRegionId(502) //return "RJ"
     * @param int $regionId
     * @return string
     */
    private function getStateByRegionId($regionId)
    {
        $standard = Mage::getModel('paymentmodule/standard');
        $region = $standard->getRegionModel()->load($regionId);

        // @fixme this method is not working!
        return 'RJ';
//        return $region->getCode();
    }

    /**
     * Gather information about customer's phones
     *
     * @return Varien_Object
     */
    private function getCustomerPhonesInformation()
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
     *
     * @return array
     */
    protected function getItemsInformation()
    {
        $items = array();

        $standard = Mage::getModel('paymentmodule/standard');
        $checkoutSession = $standard->getCheckoutSession();
        $orderId = $checkoutSession->getLastRealOrderId();

        $order = $standard->getOrderByIncrementOrderId($orderId);

        foreach ($order->getAllItems() as $item) {
            $itemInfo = array();

            $itemInfo['amount'] = round($item->getPrice() * 100);
            $itemInfo['quantity'] = (int) $item->getQtyOrdered();
            $itemInfo['description'] = 'item description';

            $items[] = $itemInfo;
        }

        return $items;
    }
}
