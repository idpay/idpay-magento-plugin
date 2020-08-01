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
namespace IDPay\IDPay\Block;

use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class IDPay extends \Magento\Framework\View\Element\Template
{

    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $messageManager;
    protected $redirectFactory;
    protected $catalogSession;
    protected $customer_session;
    protected $order;
    protected $response;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Session $customer_session,
        RedirectFactory $redirectFactory,
        \Magento\Framework\App\Response\Http $response,
        Template\Context $context,
        array $data
    )
    {
        $this->customer_session = $customer_session;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->response = $response;
        parent::__construct($context, $data);
    }

    private function getOrder()
    {
        return $this->_orderFactory->create()->load($this->getOrderId());
    }

    function changeStatus($status)
    {
        $order = $this->getOrder();
        $order->setStatus($status);
        $order->save();
    }

    public function getOrderId()
    {
        return $this->_checkoutSession->getLastRealOrder()->getIncrementId();
    }

    private function getConfig($value)
    {
        return $this->_scopeConfig->getValue('payment/idpay/' . $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAfterOrderStatus()
    {
        return $this->getConfig('after_order_status');
    }

    public function getOrderStatus()
    {
        return $this->getConfig('order_status');
    }

    protected function idpay_get_failed_message($track_id, $order_id)
    {
        return str_replace(["{track_id}", "{order_id}"], [$track_id, $order_id], $this->getConfig('failed_massage'));
    }

    protected function idpay_get_success_message($track_id, $order_id)
    {
        return str_replace(["{track_id}", "{order_id}"], [$track_id, $order_id], $this->getConfig('success_massage'));
    }

    public function redirect()
    {
        if (!$this->getOrderId()) {
            $this->response->setRedirect($this->_urlBuilder->getUrl(''));
            return "";
        }
        $response['state'] = false;
        $response['result'] = "";

        $api_key = $this->getConfig('api_key');
        $sandbox = $this->getConfig('sandbox') == 1 ? 'true' : 'false';
        $amount = intval($this->getOrder()->getGrandTotal());

        if (!empty($this->getConfig('currency')) && $this->getConfig('currency') == 1) {
            $amount *= 10;
        }

        $desc = "پرداخت سفارش شماره " . intval($this->getOrderId());
        $callback = $this->_urlBuilder->getUrl('idpay/redirect/callback');

        if (empty($amount)) {
            $response['result'] = 'واحد پول انتخاب شده پشتیبانی نمی شود.';
        }

        $data = array(
            'order_id' => $this->getOrderId(),
            'amount' => $amount,
            'phone' => $this->getOrder()->getBillingAddress()->getTelephone(),
            'desc' => $desc,
            'callback' => $callback,
        );
        $ch = curl_init('https://api.idpay.ir/v1/payment');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-API-KEY:' . $api_key,
            'X-SANDBOX:' . $sandbox,
        ));

        $result = curl_exec($ch);
        $result = json_decode($result);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 201 || empty($result) || empty($result->id) || empty($result->link)) {
            $response['result'] = sprintf('خطا هنگام ایجاد تراکنش. کد خطا: %s', $http_status);
        } else {
            $this->changeStatus($this->getOrderStatus());
            $response['state'] = true;
            $this->response->setRedirect($result->link);
        }
        return $response;
    }

    public function callback()
    {
        $data = $this->getRequest()->getParams();
        $order = $this->getOrder();
        $response['state'] = false;
        $response['result'] = "";
        if (!$order->getData() || empty($data['id']) || empty($data['order_id'])) {
            $response['result'] = "تراکنش موجود نیست یا قبلا اعتبار سنجی شده است.";
        } else {
            $amount = intval($order->getGrandTotal());

            if (!empty($this->getConfig('currency')) && $this->getConfig('currency') == 1) {
                $amount *= 10;
            }

            $orderid = $this->getOrderId();
            $pid = $data['id'];
            $porder_id = $data['order_id'];
            if (!empty($pid) && !empty($porder_id) && $porder_id == $orderid) {
                $api_key = $this->getConfig('api_key');
                $sandbox = $this->getConfig('sandbox') == 1 ? 'true' : 'false';

                $data = array(
                    'id' => $pid,
                    'order_id' => $orderid,
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1/payment/inquiry');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'X-API-KEY:' . $api_key,
                    'X-SANDBOX:' . $sandbox,
                ));

                $result = curl_exec($ch);
                $result = json_decode($result);
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_status != 200) {
                    $response['result'] = sprintf('خطا هنگام بررسی وضعیت تراکنش. کد خطا: %s', $http_status);
                    $this->changeStatus(Order::STATE_CANCELED);
                }

                $inquiry_status = empty($result->status) ? NULL : $result->status;
                $inquiry_track_id = empty($result->track_id) ? NULL : $result->track_id;
                $inquiry_order_id = empty($result->order_id) ? NULL : $result->order_id;
                $inquiry_amount = empty($result->amount) ? NULL : $result->amount;

                if (empty($inquiry_status) || empty($inquiry_track_id) || empty($inquiry_amount) || $inquiry_amount != $amount || $inquiry_status != 100 || $inquiry_order_id !== $orderid) {
                    $response['result'] = $this->idpay_get_failed_message($inquiry_track_id, $inquiry_order_id);
                    $this->changeStatus(Order::STATE_CANCELED);
                } else {
                    $response['state'] = true;
                    $response['result'] = $this->idpay_get_success_message($inquiry_track_id, $inquiry_order_id);
                    $this->changeStatus($this->getAfterOrderStatus());
                }
            } else {
                $response['result'] = 'کاربر از انجام تراکنش منصرف شده است';
                $this->changeStatus(Order::STATE_CANCELED);
            }
        }
        return $response;
    }
}