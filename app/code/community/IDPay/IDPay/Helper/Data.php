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

class Idpay_Idpay_Helper_Data extends Mage_Payment_Helper_Data
{
	
	public function getBankMessage($messageNumber) {
		
		switch($messageNumber){
			case 100:
				$msg = "تراكنش با موفقیت انجام شد.";
				break ;
			case -1:
				$msg = "اطلاعات ارسال شده ناقص است." ;
				break;
			case -2:
				$msg = "و يا مرچنت كد پذيرنده صحيح نيست. IP" ;
				break;
			case -3:
				$msg = "با توجه به محدوديت هاي شاپرك امكان پرداخت با رقم درخواست شده ميسر نمي باشد." ;
				break;
			case -4:
				$msg = "سطح تاييد پذيرنده پايين تر از سطح نقره اي است." ;
				break;
			case -11:
				$msg = "درخواست مورد نظر يافت نشد." ;
				break;		
			case -12:
				$msg = "امكان ويرايش درخواست ميسر نمي باشد." ;
				break;
			case -21:
				$msg = "هيچ نوع عمليات مالي براي اين تراكنش يافت نشد." ;
				break;
			case -22:
				$msg = "تراكنش نا موفق ميباشد." ;
				break;
			case -33:
				$msg = "رقم تراكنش با رقم پرداخت شده مطابقت ندارد." ;
				break;
			case -34:
				$msg = "سقف تقسيم تراكنش از لحاظ تعداد يا رقم عبور نموده است" ;
				break;
			case -40:
				$msg = "اجازه دسترسي به متد مربوطه وجود ندارد." ;
				break;
			case -41:
				$msg = "غيرمعتبر ميباشد. AdditionalData اطلاعات ارسال شده مربوط به" ;
				break;
			case -42:
				$msg = "مدت زمان معتبر طول عمر شناسه پرداخت بايد بين 30 دقيه تا 45 روز مي باشد." ;
				break;
			case -54:
				$msg = "درخواست مورد نظر آرشيو شده است." ;
				break;
			case 101:
				$msg = "تراكنش انجام شده است. PaymentVerification عمليات پرداخت موفق بوده و قبلا";
				break;
			default:
				$msg = "خطای جدیدی رخ داد." ; 								
		}
		
		return $msg ;
	}
}
