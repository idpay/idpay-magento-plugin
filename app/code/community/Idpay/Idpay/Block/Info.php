<?php
/**
 * Magento
 *
 * @category   Idpay
 * @package    Idpay_Idpay
 */

class Idpay_Idpay_Block_Info extends Mage_Payment_Block_Info
{
    protected function _construct() {
        parent::_construct ();
        $this->setTemplate ( 'idpay/idpay/info.phtml' );
    }
    public function getPaymentImageSrc() {
        return $this->getSkinUrl ( 'images/idpay/logo.svg' );
    }
    public function getMethodCode() {
        return $this->getInfo ()->getMethodInstance ()->getCode ();
    }
    public function toPdf() {
        $this->setTemplate ( 'idpay/idpay/pdf/info.phtml' );
        return $this->toHtml ();
    }
}
