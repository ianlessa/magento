<?php
class Mundipagg_Paymentmodule_Block_Adminhtml_Sales_Order_Totals extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $order = $this->getSource();
        //@todo get correct interest value to order.

        if ($order->getMundipaggInterest() > 0 || true) {
            $this->addTotalBefore(new Varien_Object(array
            (
                'code'  => 'mundipagg_interest',
                'field' => 'interest',
                'value' => $order->getMundipaggInterest(),
                'label' => $this->__('Juros de Parcelas')
            )), 'grand_total');
        }

        return $this;
    }
}