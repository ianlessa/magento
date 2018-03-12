<?php

class Mundipagg_Paymentmodule_Block_Form_Builder extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentmodule/form/builder.phtml');
    }

    public function getStructure()
    {
        $model = $this->getModelName($this->getMethodCode());
        if (!$model) {
            // @todo think about exception
            // @todo log it
        }

        return $model->getPaymentStructure();
    }

    private function getModelName($code)
    {
        $code = explode('_', $code);
        return Mage::getModel('paymentmodule/' . end($code));
    }

    public function getPartialHTML($element)
    {
        // @fixme new method
        $this->standard = Mage::getModel('paymentmodule/standard');

        $checkout = Mage::getSingleton('checkout/session');
        $grandTotal = $checkout->getQuote()->getGrandTotal();

        $retn = $this->getLayout();

        $retn = $retn->createBlock("paymentmodule/form_partial_$element",'',
            [
                'code' => $this->getMethodCode(),
                'element_index' => $this->getIndexFor($element),
                'show_value_input' => count($this->getStructure()) > 1,
                'grand_total' => number_format($grandTotal, "2", ",", "")
            ]
        );

        $retn = $retn->toHtml();

        return $retn;
    }

    public function getIndexFor($element)
    {
        $elementCount = $this->getElementCount();

        if ($elementCount == null) {
           $elementCount = [];
        }

        if (!isset($elementCount[$element])) {
            $elementCount[$element] = 0;
        }

        $elementCount[$element]++;
        $this->setElementCount($elementCount);

        return $this->elementCount[$element];
    }
}