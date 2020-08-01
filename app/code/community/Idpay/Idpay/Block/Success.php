<?php
/**
 * Magento
 *
 * @category   Idpay
 * @package    Idpay_Idpay
 */

class Idpay_Idpay_Block_Success extends Mage_Core_Block_Template
{
    protected function _toHtml() {
        $status   = empty( $_POST['status'] ) ? NULL : $_POST['status'];
        $track_id = empty( $_POST['track_id'] ) ? NULL : $_POST['track_id'];
        $id       = empty( $_POST['id'] ) ? NULL : $_POST['id'];
        $order_id = empty( $_POST['order_id'] ) ? NULL : $_POST['order_id'];

        $session = Mage::getSingleton('checkout/session');

        $oderId   = Mage::helper ( 'core' )->decrypt(Mage::getSingleton('core/session')->getOrderId());
        Mage::getSingleton('core/session')->unsOrderId();

        $this->_order = new Mage_Sales_Model_Order();
        $this->_order->loadByIncrementId($session->getLastRealOrderId());
        $this->_paymentInst = $this->_order->getPayment()->getMethodInstance();

        $idpay_id = $session->getData( 'idpay_id' );
        if($order_id != $oderId || empty($id) || empty($oderId) || $id != $idpay_id){
            $session->setErrorMessage($this->__('The input parameters are invalid.')) ;
            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script></body></html>';
            return $html;
        }

        if ( $status != 10 ){
            $msg = Mage::Helper('idpay')->getBankMessage( $status );
            $msg = sprintf( $this->__( 'Error: %s (Code: %s), Track id: %s' ), $msg, $status, $track_id );

            $this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CLOSED, $msg, true );
            $this->_order->save ();
            $this->_order->sendOrderUpdateEmail (true, $msg);
            $session->setErrorMessage($msg) ;
            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script></body></html>';
            return $html;
        }

        require_once Mage::getBaseDir().DS.'lib'.DS.'Zend'.DS.'Log.php';

        $api_key = Mage::Helper ('core')->decrypt( $this->_paymentInst->getConfigData ('api_key') );
        $sandbox = $this->_paymentInst->getConfigData ('sandbox')? 'true' : 'false';

        $data = [
            'id'       => $id,
            'order_id' => $order_id,
        ];

        $ch = curl_init( 'https://api.idpay.ir/v1.1/payment/verify' );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-KEY:' . $api_key,
            'X-SANDBOX:' . $sandbox,
        ] );

        $result      = curl_exec( $ch );
        $result      = json_decode( $result );
        $http_status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( $http_status != 200 )
        {
            $msg = sprintf( $this->__( 'Error: %s (Code: %s), Track id: %s' ), $result->error_message, $result->error_code, $track_id );
            $this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CLOSED, $msg, true );
            $this->_order->save ();
            $this->_order->sendOrderUpdateEmail (true, $msg);
            $session->setErrorMessage( $msg ) ;
            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'idpay/redirect/redirect', array ('_secure' => true) ) . '" </script></body></html>';
            return $html;
        }

        $verify_status   = empty( $result->status ) ? NULL : $result->status;
        $verify_track_id = empty( $result->track_id ) ? NULL : $result->track_id;
        $verify_amount   = empty( $result->amount ) ? NULL : $result->amount;
        $verify_card_no  = empty( $result->payment->card_no ) ? NULL : $result->payment->card_no;

        if ( empty( $verify_status ) || empty( $verify_track_id ) || empty( $verify_amount ) || $verify_status < 100 )
        {
            $msg = sprintf( $this->__( 'Error: %s (Code: %s), Track id: %s' ), Mage::Helper('idpay')->getBankMessage( $verify_status ), $verify_status, $track_id );
            $this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CLOSED, $msg, true );
            $this->_order->save ();
            $this->_order->sendOrderUpdateEmail (true, $msg);
            $session->setErrorMessage($msg) ;
            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'idpay/redirect/redirect', array ('_secure' => true) ) . '" </script></body></html>';
            return $html;
        }
        else
        { // Payment is successful.
            if ($this->_order->canInvoice ()) {
                try {
                    $invoice = $this->_order->prepareInvoice ();
                    $invoice->register ()->capture ();
                    Mage::getModel ( 'core/resource_transaction' )
                        ->addObject ( $invoice )
                        ->addObject ( $invoice->getOrder() )
                        ->save ();

                    $this->addTransaction($this->_order, $verify_track_id, (array)$result->payment);

                    $message = sprintf($this->__('Payment succeeded. Status: %s, Track id: %s, Card no: %s'), $verify_status, $verify_track_id, $verify_card_no);

                    $status = $this->getConfigData ('second_order_status');
                    $status = !empty($status)? $status : Mage_Sales_Model_Order::STATE_COMPLETE;
                    $this->_order->addStatusToHistory ( $status, sprintf($this->__('Card Hashed Value: %s'), $result->payment->hashed_card_no), false );
                    $this->_order->addStatusToHistory ( $status, $message, true );
                    $this->_order->save ();
                    $this->_order->sendNewOrderEmail ();
                }
                catch (Exception $e) {
                    $this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CLOSED, $e, true );
                    $this->_order->save ();
                }

                Mage::getSingleton('core/session')->addSuccess($message);
                $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/success', array ('_secure' => true ) ) . '" </script> </body></html>';
                return $html;
            }
        }
    }

    public function addTransaction($order, $txnId, $paymentData = array()) {
        $transaction = Mage::getModel('sales/order_payment_transaction');
        $transaction->setOrderId($order->getId());
        $transaction->setTxntId($txnId);
        $transaction->setTxnType('capture');
        $transaction->setIsClosed(true);

        $payment = $order->getPayment();
        $payment->setLastTransId($txnId);
        $payment->setTransactionId($txnId);
        $payment->save();

        $transaction->setOrderPaymentObject($payment);
        foreach ($this->$paymentData as $key => $value) {
            $transaction->setAdditionalInformation($key, $value);
        }
        $transaction->save();
    }
}
