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

class Idpay_Idpay_Helper_Data extends Mage_Payment_Helper_Data
{
	public function getBankMessage($messageNumber) {
        switch ($messageNumber){
            case 1:
                return 'پرداخت انجام نشده است';
                break;
            case 2:
                return 'پرداخت ناموفق بوده است';
                break;
            case 3:
                return 'خطا رخ داده است';
                break;
            case 4:
                return 'بلوکه شده';
                break;
            case 5:
                return 'برگشت به پرداخت کننده';
                break;
            case 6:
                return 'برگشت خورده سیستمی';
                break;
            case 7:
                return 'انصراف از پرداخت';
                break;
            case 8:
                return 'به درگاه پرداخت منتقل شد';
                break;
            case 10:
                return 'در انتظار تایید پرداخت';
                break;
            case 100:
                return 'پرداخت تایید شده است';
                break;
            case 101:
                return 'پرداخت قبلا تایید شده است';
                break;
            case 200:
                return 'به دریافت کننده واریز شد';
                break;
        }
	}
}
