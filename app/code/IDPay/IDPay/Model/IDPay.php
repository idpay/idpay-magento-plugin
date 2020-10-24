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
namespace IDPay\IDPay\Model;

class IDPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'idpay';
    protected $_isOffline = true;
}
