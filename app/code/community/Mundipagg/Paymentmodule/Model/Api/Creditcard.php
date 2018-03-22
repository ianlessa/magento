<?php

require_once Mage::getBaseDir('lib') . '/autoload.php';

use MundiAPILib\Models\CreateCustomerRequest;
use MundiAPILib\Models\CreateAddressRequest;
use MundiAPILib\Models\CreatePaymentRequest;
use MundiAPILib\Models\CreateCreditCardPaymentRequest;

class Mundipagg_Paymentmodule_Model_Api_Creditcard extends Mundipagg_Paymentmodule_Model_Api_Standard
{
    public function getPayment($paymentInfo)
    {
        $monetary = Mage::helper('paymentmodule/monetary');

        $result = [];

        foreach ($paymentInfo as $payment) {
            $paymentRequest = new CreatePaymentRequest();

            $creditCardPaymentRequest = new CreateCreditCardPaymentRequest();

            $creditCardPaymentRequest->installments = $payment['creditCardInstallments'];
            $creditCardPaymentRequest->cardToken = $payment['token'];

            $paymentRequest->paymentMethod = 'credit_card';
            $paymentRequest->creditCard = $creditCardPaymentRequest;
            $paymentRequest->amount = $monetary->toCents($payment['value']);
            // @todo correct this when implementing multi buyers
            if(false) {
                $paymentRequest->customer = $this->getCustomer();
            }
            // @todo this should not be hard coded
            $paymentRequest->currency = 'BRL';

            $result[] = $paymentRequest;
        }

        return $result;
    }

    private function getCustomer()
    {
        $customerRequest = new CreateCustomerRequest();

        $customerRequest->name = 'John Doe';
        $customerRequest->address = $this->getAddress();
        $customerRequest->type = 'individual';

        return $customerRequest;
    }

    private function getAddress()
    {
        $addressRequest = new CreateAddressRequest();

        $addressRequest->street = 'Fake Street';
        $addressRequest->number = 23;
        $addressRequest->zipCode = '24420023';
        $addressRequest->neighborhood = 'Comptown';
        $addressRequest->city = 'San Andreas';
        $addressRequest->state = 'RJ';
        $addressRequest->complement = 'Far from here';
        $addressRequest->country = 'BR';

        return $addressRequest;
    }
}
