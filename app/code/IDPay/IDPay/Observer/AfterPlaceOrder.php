<?php
/**
 * IDPay payment gateway
 *
 * @developer JMDMahdi
 * @publisher IDPay
 * @copyright (C) 2018 IDPay
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
        return true;
    }
}