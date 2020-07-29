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
 
class Idpay_Idpay_Model_zarinpalweb extends Mage_Payment_Model_Method_Abstract
{
	protected $_code = 'zarinpalweb';	
	protected $_formBlockType = 'zarinpalweb/form';
	protected $_infoBlockType = 'zarinpalweb/info';	
	protected $_isGateway               = false;
	protected $_canAuthorize            = true;
	protected $_canCapture              = true;
	protected $_canCapturePartial       = false;
	protected $_canRefund               = false;
	protected $_canVoid                 = false;
	protected $_canUseInternal          = false;
	protected $_canUseCheckout          = true;
	protected $_canUseForMultishipping  = false;	
	protected $_order;
	
	public function getOrder()	{
		if (! $this->_order) {
			$paymentInfo = $this->getInfoInstance ();
			$this->_order = Mage::getModel ( 'sales/order' )->loadByIncrementId ( $paymentInfo->getOrder ()->getRealOrderId () );
		}
		return $this->_order;
	}
	
	public function validate() {
		$quote = Mage::getSingleton ( 'checkout/session' )->getQuote ();
		$quote->setCustomerNoteNotify ( false );
		parent::validate ();
	}
	
	public function getOrderPlaceRedirectUrl()	{
		return Mage::getUrl ( 'zarinpalweb/redirect/redirect', array ('_secure' => true ) );
	}
	
	public function capture(Varien_Object $payment, $amount)	{
		$payment->setStatus ( self::STATUS_APPROVED )->setLastTransId ( $this->getTransactionId () );
		return $this;
	}
	
	public function getPaymentMethodType()	{
		return $this->_paymentMethod;
	}
	
	public function getUrl() {
		require_once Mage::getBaseDir().DS.'lib'.DS.'Zend'.DS.'Log.php';
		require_once Mage::getBaseDir().DS.'lib'.DS.'nusoap'.DS.'nusoap.php';
		
		$gateway = $this->getConfigData ('gateway');
		
		$client = new nusoap_client($gateway, 'wsdl');
        $client->soap_defencoding = 'UTF-8';
		
			$MerchantID 	= Mage::helper ('core')->decrypt($this->getConfigData ('terminal_Id')) ; 
			$orderId 		= $this->getOrder ()->getRealOrderId ();
			Mage::getSingleton('core/session')->setOrderId(Mage::helper ('core')->encrypt($this->getOrder ()->getRealOrderId ()));
			$amount 		= intval($this->getOrder ()->getGrandTotal ());
			$Description = "پرداخت شماره سفارش $orderId";
			$callBackUrl 	= ($this->getConfigData ('ssl_enabled')? 'https://' :'http://').$_SERVER['HTTP_HOST'].'/index.php' .'/zarinpalweb/redirect/success/';

			    $parameters = array(
					'MerchantID' 	=> $MerchantID,
					'Amount' 		=> $amount,
					'Description'=> $Description,
					'CallbackURL' 	=> $callBackUrl
			    );
		    
				// Call the SOAP method
				$result = $client->call('PaymentRequest', array($parameters));
				if ($result['Status'] == 100) {
						$pgwpay_url = $this->getConfigData ('pgwpay_url') ;
					} else {
						$msg 	= Mage::Helper('zarinpalweb')->getBankMessage($result['Status']);
						$this->getOrder ();													
						$this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CANCELED, $this->_getHelper()->__($msg), true );
						$this->_order->save ();			
						Mage::getSingleton('checkout/session')->setErrorMessage($this->_getHelper()->__($msg)) ;				
            	}
		return $result ;
}
	public function getFormFields() {
		$orderId = $this->getOrder ()->getRealOrderId ();
		//$customerId = Mage::getSingleton ( 'customer/session' )->getCustomerId (); 
		$params = array('x_invoice_num' => $orderId) ;
		return $params;
	}
}