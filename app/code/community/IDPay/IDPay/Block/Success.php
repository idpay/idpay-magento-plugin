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

class Idpay_Idpay_Block_Success extends Mage_Core_Block_Template
{
	protected function _toHtml() {
        $Authority 			= $_GET['Authority'];				 
		$Status 			= $_GET['Status'];		
		$oderId 			=  Mage::helper ( 'core' )->decrypt(Mage::getSingleton('core/session')->getOrderId());
		Mage::getSingleton('core/session')->unsOrderId();
			$order = new Mage_Sales_Model_Order();
            $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            $order->loadByIncrementId($incrementId);
 			$this->_paymentInst = $order->getPayment()->getMethodInstance();
 			if ($Status == 'NOK') {
 			    $order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CANCELED, $content, true );
					$order->save ();											
					Mage::getSingleton('checkout/session')->setErrorMessage($content) ;		
					$html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script></body></html>';		
					return $html;
 			}
		if ($Status == 'OK') {
			require_once Mage::getBaseDir().DS.'lib'.DS.'Zend'.DS.'Log.php';
			require_once Mage::getBaseDir().DS.'lib'.DS.'nusoap'.DS.'nusoap.php';
			$gateway = $this->_paymentInst->getConfigData ('gateway');
			$client = new nusoap_client($gateway, 'wsdl');
			$client->soap_defencoding = 'UTF-8';
			if ( (!$client) OR ($err = $client->getError()) ) {
				$this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper ( 'zarinpalweb' )->__('Could not connect to bank or service.'), true );
				$this->_order->save ();											
				Mage::getSingleton('checkout/session')->setErrorMessage($this->__('Could not connect to bank or service.')) ;		
				$html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script></body></html>';		
				return $html;
				
			}else{
				
				$MerchantID 	= Mage::helper ( 'core' )->decrypt($this->_paymentInst->getConfigData ('terminal_Id')) ; 
				$amount = intval($order->getGrandTotal());
	  			 $parameters = array(
					'MerchantID' 		=> $MerchantID,
					'Authority' 			=> $Authority,
	  			 	'Amount'		=> $amount
			    );
			    $i = 3; //to garantee the connection and authorization, this process should be repeat maximum 10 times
				do{		   
		      		// Call the SOAP method
					$verify_result = $client->call('PaymentVerification', $parameters);
					if ($client->fault || ($err = $client->getError())) {
						$i -= 1;
					}else{
						$i = 0 ;
					}
           	 	} while($i>0);
           	 	
		  		if ($client->fault){            	
	            	ob_start();
					echo "<h2>خطا</h2><pre>" ;
					print_r($verify_result);
					echo "<pre>" ;
	            	$content = ob_get_contents();
	            	ob_end_clean();
	            	
	            	$order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CANCELED, $content, true );
					$order->save ();											
					Mage::getSingleton('checkout/session')->setErrorMessage($content) ;		
					$html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script></body></html>';		
					return $html;
	            }else{
	            		
					$err = $client->getError();
					if ($err) {
						// Display the error	

						$order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CANCELED, $err, true );
						$order->save ();											
						Mage::getSingleton('checkout/session')->setErrorMessage($err) ;		
						$html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script></body></html>';		
						return $html;
						
					} 
					else {
						if($verify_result['Status'] == 100){																
										if ($order->canInvoice ()) {
											$invoice = $order->prepareInvoice ();
											$invoice->register ()->capture ();
											Mage::getModel ( 'core/resource_transaction' )
												->addObject ( $invoice )
												->addObject ( $invoice->getOrder() )
												->save ();
										$message = sprintf($this->__("Yours order track number is %s"),$Authority);
										$order->addStatusToHistory ( $this->_paymentInst->getConfigData ( 'second_order_status' ), Mage::helper ( 'zarinpalweb' )->__( Mage::Helper('zarinpalweb')->getBankMessage($verify_result['Status'])) . " " . $message, true );			
										$order->save ();							
										$order->sendNewOrderEmail ();
										Mage::getSingleton('core/session')->addSuccess($message);
										$html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/success', array ('_secure' => true ) ) . '" </script> </body></html>';
										return $html;
								}
					}
				            }
	            
	            }
			}
		}
		else {
			//Other feature of the resCode
			$msg = Mage::Helper('zarinpalweb')->getBankMessage($verify_result['Status']);
			$this->_order = Mage::getModel ( 'sales/order' )->loadByIncrementId ( $saleOrderId );												
			$this->_order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper ( 'zarinpalweb' )->__( $msg), true );
			$this->_order->save ();
			$this->_order->sendOrderUpdateEmail (true, $msg);						
			Mage::getSingleton('checkout/session')->setErrorMessage($this->__($msg)) ;		
			$html = '<html><body> <script type="text/javascript"> window.location = "' . Mage::getUrl ( 'checkout/onepage/failure', array ('_secure' => true) ) . '" </script> این یک تست است </body></html>';		
			return $html;
	}
	}
}