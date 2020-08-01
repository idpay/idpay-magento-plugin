<?php
/**
 * Magento
 *
 * @category   Idpay
 * @package    Idpay_Idpay
 */

class Idpay_Idpay_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml() {
        $module = 'idpay/idpay';
        $payment = $this->getOrder ()->getPayment ()->getMethodInstance ();
        $res = $payment->getUrl() ;
        if( !empty( $res->link ) ) {
            error_log( 'Authority' . $res->id );
            $html = '<html><body> <script type="text/javascript"> window.location = "'. $res->link . '"</script> </body></html>';
        }else{
            $html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script> </body></html>';
        }
        return $html;
    }
}