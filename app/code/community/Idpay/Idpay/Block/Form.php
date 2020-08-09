<?php
/**
 * Magento
 *
 * @category   Idpay
 * @package    Idpay_Idpay
 */

class Idpay_Idpay_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct() {
        parent::_construct ();
        $this->setTemplate ( 'idpay/idpay/form.phtml' );
    }

    public function getPaymentImageSrc() {
        return $this->getSkinUrl ( 'images/idpay/logo.svg' );
    }
}
