<?php

class Mundipagg_Paymentmodule_Helper_Interest extends Mage_Core_Helper_Abstract
{
    public function getInterestValue($installmentNum,$orderTotal,$cards = null, $cardBrand = 'default')
    {
        $installmentHelper = Mage::helper('paymentmodule/installment');
        $allInstallments = $installmentHelper->getInstallments($orderTotal,$cards);

        $installmentInterest = 0;
        $brandInstallments = [];
        if (isset($allInstallments[$cardBrand])) {
            $brandInstallments = $allInstallments[$cardBrand];
        }
        foreach($brandInstallments as $installment) {
            if ($installment['times'] == $installmentNum) {
                $installmentInterest = $installment['interest'];
                break;
            }
        }
        $interest = $orderTotal * ($installmentInterest / 100);

        return round($interest,2);
    }
}