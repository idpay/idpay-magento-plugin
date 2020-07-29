<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * @category   Persian Magento
 * @package    Idpay_Idpay
 * @copyright  Copyright (c) 1396 Persian Magento (http://www.persian-magento.ir)
 */
 
class Idpay_Idpay_Block_Form extends Mage_Payment_Block_Form
{
	protected function _construct() {
		parent::_construct ();
		$this->setTemplate ( 'persianmagento/zarinpalweb/form.phtml' );
	}
	
	public function getPaymentImageSrc() {
		return $this->getSkinUrl ( 'images/persianmagento/zarinpalweb.png' );
	}
	
}