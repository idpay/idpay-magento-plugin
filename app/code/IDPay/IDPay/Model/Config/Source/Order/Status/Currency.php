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
namespace IDPay\IDPay\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;

class Currency extends Status
{
    protected $_stateStatuses = [
        "RIAL",
        "TOMAN"
    ];
}
