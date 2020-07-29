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
 
class Idpay_Idpay_Block_Info extends Mage_Payment_Block_Info
{
	protected function _construct() {
		parent::_construct ();
		$this->setTemplate ( 'persianmagento/zarinpalweb/info.phtml' );
	}
	public function getMethodCode() {
		return $this->getInfo ()->getMethodInstance ()->getCode ();
	}
	public function toPdf() {
		$this->setTemplate ( 'persianmagento/zarinpalweb/pdf/info.phtml' );
		return $this->toHtml ();
	}
	
}