<?php
/**
 * IDPay payment gateway
 *
 * @developer JMDMahdi, meysamrazmi, vispa
 * @publisher IDPay
 * @copyright (C) 2020 IDPay
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 *
 * http://idpay.ir
 */
namespace IDPay\IDPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\UrlInterface;

class AfterPlaceOrder implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderId = $observer->getEvent()->getOrder()->getId();

        $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
        setcookie('idpay_order_id', $orderId, time()+3600, '/', $domain, false);
    }

}
