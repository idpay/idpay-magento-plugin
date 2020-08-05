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
namespace IDPay\IDPay\Model;

class IDPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'idpay';
    protected $_isOffline = true;
}
