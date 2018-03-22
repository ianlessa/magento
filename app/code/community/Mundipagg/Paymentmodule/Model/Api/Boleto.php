<?php

require_once Mage::getBaseDir('lib') . '/autoload.php';

use MundiAPILib\Models\CreateCustomerRequest;
use MundiAPILib\Models\CreateAddressRequest;
use MundiAPILib\Models\CreatePaymentRequest;
use MundiAPILib\Models\CreateBoletoPaymentRequest;

class Mundipagg_Paymentmodule_Model_Api_Boleto extends Mundipagg_Paymentmodule_Model_Api_Standard
{
    public function getPayment($paymentInfo)
    {
        $boletoConfig = Mage::getModel('paymentmodule/config_boleto');
        $monetary = Mage::helper('paymentmodule/monetary');

        $bank = $boletoConfig->getBank();
        $instructions = $boletoConfig->getInstructions();
        $dueAt = $boletoConfig->getDueAt();

        $result = [];

        foreach ($paymentInfo as $payment) {
            $paymentRequest = new CreatePaymentRequest();

            $boletoPaymentRequest = new CreateBoletoPaymentRequest();

            $boletoPaymentRequest->bank = $bank;
            $boletoPaymentRequest->instructions = $instructions;
            $boletoPaymentRequest->dueAt = $dueAt;

            $paymentRequest->paymentMethod = 'boleto';
            $paymentRequest->boleto = $boletoPaymentRequest;
            $paymentRequest->amount = $monetary->toCents($payment['value']);
            // @todo correct this when implementing multi buyers
            if(false) {
                $paymentRequest->customer = $this->getCustomer($payment['taxvat']);
            }
            // @todo this should not be hard coded
            $paymentRequest->currency = 'BRL';

            $result[] = $paymentRequest;
        }

        return $result;
    }

    private function getCustomer($documentNumber)
    {
        $customerRequest = new CreateCustomerRequest();

        $customerRequest->name = 'John Doe';
        $customerRequest->document = $documentNumber;
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
