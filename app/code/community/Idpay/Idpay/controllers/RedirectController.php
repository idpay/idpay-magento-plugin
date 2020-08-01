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

class Idpay_Idpay_RedirectController extends Mage_Core_Controller_Front_Action
{
    protected $_redirectBlockType = 'idpay/redirect';
    protected $_successBlockType = 'idpay/success';
    protected $_failureBlockType = 'idpay/failure';
    protected $_sendNewOrderEmail = true;
    protected $_order = NULL;
    protected $_paymentInst = NULL;
    protected $_transactionID = NULL;
    protected function _expireAjax() {
        if (! $this->getCheckout ()->getQuote ()->hasItems ()) {
            $this->getResponse ()->setHeader ( 'HTTP/1.1', '403 Session Expired' );
            exit ();
        }
    }

    public function getCheckout() {
        return Mage::getSingleton ( 'checkout/session' );
    }

    public function redirectAction() {
        $session = $this->getCheckout ();
        $session->setidpayQuoteId ( $session->getQuoteId () );
        $session->setidpayRealOrderId ( $session->getLastRealOrderId () );

        $order = Mage::getModel ( 'sales/order' );
        $order->loadByIncrementId ( $session->getLastRealOrderId () );

        $this->_order = $order;
        $this->_paymentInst = $this->_order->getPayment ()->getMethodInstance ();

        $this->getResponse ()->setBody ( $this->getLayout ()->createBlock ( $this->_redirectBlockType )->setOrder ( $order )->toHtml () );
        $session->unsQuoteId ();
    }

    public function successAction() {
        $session = $this->getCheckout ();
        $session->unsidpayRealOrderId ();
        $session->setQuoteId ( $session->getidpayQuoteId ( true ) );
        $session->getQuote ()->setIsActive ( false )->save ();

        $order = Mage::getModel ( 'sales/order' );
        $order->load ( $this->getCheckout ()->getLastOrderId () );
        $this->_order = $order;

        $this->getResponse ()->setBody ( $this->getLayout ()->createBlock ( $this->_successBlockType )->setOrder ( $order )->toHtml () );
    }
}
