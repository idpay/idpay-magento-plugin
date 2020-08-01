<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * @category   Idpay Magento
 * @package    Idpay_Idpay
 * @copyright  Copyright (c) 1396 Idpay Magento (http://www.Idpay-magento.ir)
 */

class Idpay_Idpay_Model_idpay extends Mage_Payment_Model_Method_Abstract
{
    protected $_code                    = 'idpay';
    protected $_formBlockType           = 'idpay/form';
    protected $_infoBlockType           = 'idpay/info';
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

    public function getOrder() {
        if (! $this->_order) {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = Mage::getModel( 'sales/order' )->loadByIncrementId( $paymentInfo->getOrder()->getRealOrderId() );
        }
        return $this->_order;
    }

    public function validate() {
        $quote = Mage::getSingleton ( 'checkout/session' )->getQuote ();
        $quote->setCustomerNoteNotify ( false );
        parent::validate ();
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl ( 'idpay/redirect/redirect', array ('_secure' => true ) );
    }

    public function capture(Varien_Object $payment, $amount) {
        $payment->setStatus ( self::STATUS_APPROVED )->setLastTransId ( $this->getTransactionId () );
        return $this;
    }

    public function getPaymentMethodType() {
        return $this->_paymentMethod;
    }

    public function getUrl() {
        require_once Mage::getBaseDir().DS.'lib'.DS.'Zend'.DS.'Log.php';

        $api_key 	 = Mage::helper ('core')->decrypt( $this->getConfigData ('api_key') );
        $sandbox 	 = $this->getConfigData ('sandbox')? 'true' : 'false';
        $orderId 	 = $this->getOrder ()->getRealOrderId ();
        Mage::getSingleton('core/session')->setOrderId(Mage::helper ('core')->encrypt($this->getOrder ()->getRealOrderId ()));
        $amount 	 = intval($this->getOrder ()->getGrandTotal ());
        $Description = sprintf( $this->_getHelper()->__("Pay for Order %d"), $orderId );
        $callBackUrl = ($this->getConfigData ('ssl_enabled')? 'https://' :'http://').$_SERVER['HTTP_HOST'].'/index.php' .'/idpay/redirect/success/';

        $data = [
            'order_id' => $orderId,
            'amount'   => $amount,
//            'name'     => $name,
//            'phone'    => $phone,
//            'mail'     => $mail,
            'desc'     => $Description,
            'callback' => $callBackUrl,
        ];

        $ch = curl_init( 'https://api.idpay.ir/v1.1/payment' );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-KEY:' . $api_key,
            'X-SANDBOX:' . "$sandbox",
        ] );

        $result      = curl_exec( $ch );
        $result      = json_decode( $result );
        $http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( $http_status != 201 || empty( $result ) || empty( $result->id ) || empty( $result->link ) )
        {
            $msg = sprintf( $this->_getHelper()->__( 'Error: %s (Code: %s)' ), $result->error_message, $result->error_code );

            $this->getOrder ();
            $this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CLOSED, $msg, true );
            $this->_order->save ();

            Mage::getSingleton('checkout/session')->setErrorMessage($msg);
        }
        else{
            Mage::getSingleton('checkout/session')->addData( array('idpay_id'=> $result->id) );

            $status = $this->getConfigData ('order_status');
            if( !empty($status) ){
                $this->getOrder ();
                $this->_order->setStatus( $status );
                $this->_order->save ();
            }
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