<?php
/**
 * IDPay payment gateway
 *
 * @developer JMDMahdi, meysamrazmi, vispa
 * @publisher IDPay
 * @copyright (C) 2020 IDPay
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 *
 * https://idpay.ir
 */
namespace IDPay\IDPay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Interceptor as Order;

class AfterPlaceOrder implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
        setcookie('idpay_order_id', $order->getId() , time()+3600, '/', $domain, false);
    }

}
