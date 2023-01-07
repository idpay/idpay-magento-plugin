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
namespace IDPay\IDPay\Block;

use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;


class IDPay extends Template
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
    protected $session;
    protected $transactionBuilder;

    public function __construct(SessionCheckout $checkoutSession,OrderFactory $orderFactory,ManagerInterface $messageManager,
                                Session $customer_session,RedirectFactory $redirectFactory, Http $response,
                                BuilderInterface $transactionBuilder,Template\Context $context, array $data)
    {
        $this->customer_session = $customer_session;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->messageManager = $messageManager;
        $this->redirectFactory = $redirectFactory;
        $this->response = $response;
        $this->transactionBuilder = $transactionBuilder;
        parent::__construct($context, $data);
    }

    private function getOrder($orderId = null): Order
    {
        if (! $this->order) {
            $orderId = !empty($orderId) ? $orderId : ($_COOKIE['idpay_order_id'] ?? false) ;
            $this->order = $this->_orderFactory->create()->load(($orderId));

        }
        return $this->order;
    }

    public  function isOnceEmpty( array $variables ): bool {
        foreach ( $variables as $variable ) {
            if ( empty( $variable ) ) {
                return true;
            }
        }

        return false;
    }

    public function changeStatus($status, $msg = null)
    {
        $order = $this->getOrder();
        if (empty($msg)) {
            $order->setStatus($status);
        } else {
            $order->addStatusToHistory($status, $msg, true);
        }
        $order->save();
    }

    private function getConfig($value)
    {
        return $this->_scopeConfig->getValue('payment/idpay/' . $value, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getAfterOrderStatus()
    {
        return $this->getConfig('after_order_status');
    }

    public function getBeforeOrderStatus()
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

    private function saveDoubleSpendingId(string $orderId,string $transactionId): void
    {
          $domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;
         $hash = hash('sha256',($orderId . $transactionId));
          setcookie('idpay_hash', $hash , time()+3600, '/', $domain, false);
    }

    private function isNotDoubleSpending(string $orderId,string $transactionId,string $cookieHash)
    {
        $hash = hash('sha256',($orderId . $transactionId));
        return $hash != $cookieHash ;

    }

    public function doPayment(){
        $orderId = $this->getOrder()->getId();
        if (!$orderId) {
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

        $desc = "پرداخت سفارش شماره " . $orderId;
        $callback = $this->_urlBuilder->getUrl('idpay/redirect/callback');

        if (empty($amount)) {
            $response['result'] = 'واحد پول انتخاب شده پشتیبانی نمی شود.';

            $this->changeStatus(Order::STATE_CLOSED, $response['result']);
            $this->messageManager->addErrorMessage($response['result']);

            $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
        }

        $billing  = $this->getOrder()->getBillingAddress();
        $email = !empty($billing->getEmail()) ? $billing->getEmail() :  $this->getOrder()->getCustomerEmail();

        $data = [
            'order_id' => $orderId,
            'amount' => $amount,
            'name' => $billing->getFirstname() . ' ' . $billing->getLastname(),
            'phone' => $billing->getTelephone(),
            'mail' => $email,
            'desc' => $desc,
            'callback' => $callback,
        ];
        $ch = curl_init('https://api.idpay.ir/v1.1/payment');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-KEY:' . $api_key,
            'X-SANDBOX:' . $sandbox,
        ]);

        $result = curl_exec($ch);
        $result = json_decode($result);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 201 || empty($result) || empty($result->id) || empty($result->link)) {
            $response['result'] = sprintf('خطا: %s. کد خطا: %s', $result->error_message, $result->error_code);

            $this->changeStatus(Order::STATE_CLOSED, $response['result']);
            $this->messageManager->addErrorMessage($response['result']);

            $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
        } else {
            $this->changeStatus($this->getBeforeOrderStatus());
            $response['state'] = true;
            $this->response->setRedirect($result->link);
        }
        $this->saveDoubleSpendingId($orderId,$result->id);
        return $response;
    }


    public function redirect()
    {
       return $this->doPayment();
    }

    public function doCallback(){
        $data = $this->getRequest()->getParams();
        // check not empty

        $orderId = $data['order_id'] ?? null;
        $transId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $trackId = $data['track_id'] ?? null;
        $amount = $data['amount'] ?? null;
        $response['state'] = false;
        $response['result'] = "";

        $order =  $this->getOrder($orderId ?? false);
        $orderData = $order->getData();

        $validation = [
            $orderId,
            $transId,
            $status,
            $trackId,
            $amount,
            $order,
            $orderData,
        ];

        if ( $order->getStatus() != Order::STATE_PENDING_PAYMENT || $this->isOnceEmpty($validation) ) {
            $response['result'] = "تراکنش موجود نیست یا قبلا اعتبار سنجی شده است.";
        } elseif ($this->isNotDoubleSpending($orderId,$transId,$_COOKIE['idpay_hash'])){
            $response['result'] = "در حال سواستفاده از تراکنش ";
        }
        else {
            $amount = intval($order->getGrandTotal());
            $amount = (!empty($this->getConfig('currency')) && $this->getConfig('currency') == 1) ? ($amount * 10) : $amount;

            if ($data['status'] == 10) {

                $data =['id' => $transId, 'order_id' => $orderId] ;
                $api_key = $this->getConfig('api_key');
                $sandbox =  $this->getConfig('sandbox') == 1 ? 'true' : 'false';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://api.idpay.ir/v1.1/payment/verify');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'X-API-KEY:' . $api_key,
                    'X-SANDBOX:' .$sandbox,
                ]);

                $result = curl_exec($ch);
                $result = json_decode($result);
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_status != 200) {
                    $response['result'] = sprintf('خطا هنگام بررسی وضعیت تراکنش. کد خطا: %s, پیام خطا: %s', $result->error_code, $result->error_message);
                    $this->changeStatus(Order::STATE_CANCELED, $response['result']);
                    $this->messageManager->addErrorMessage($response['result']);
                    $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
                }

                $verify_status = empty($result->status) ? null : $result->status;
                $verify_track_id = empty($result->track_id) ? null : $result->track_id;
                $verify_order_id = empty($result->order_id) ? null : $result->order_id;
                $verify_amount = empty($result->amount) ? null : $result->amount;

                if (empty($verify_status) || empty($verify_track_id) || empty($verify_amount) || $verify_amount != $amount || $verify_status != 100) {
                    $response['result'] = $this->idpay_get_failed_message($verify_track_id, $verify_order_id);
                    $this->changeStatus(Order::STATE_CANCELED, $response['result']);
                    $this->messageManager->addErrorMessage($response['result']);
                    $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
                } else {
                    $response['state'] = true;
                    $response['result'] = $this->idpay_get_success_message($verify_track_id, $verify_order_id);
                    $this->addTransaction($order, $verify_track_id, (array)$result->payment);
                    $order->addStatusToHistory($this->getAfterOrderStatus(), sprintf('<pre>%s</pre>', print_r($result->payment, true)), false);
                    $order->save();
                    $this->changeStatus($this->getAfterOrderStatus(), $response['result']);
                    $this->messageManager->addSuccessMessage($response['result']);
                    $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true]));
                }
            } else {
                $errorStatus = sprintf('خطا: %s (کد: %s)', $this->getStatus($data['status']), $data['status']);
                $errorTransaction = 'پارامتر های ورودی اشتباه هستند.';
                $response['result'] = !empty($transId) ? $errorStatus : $errorTransaction;
                $this->changeStatus(Order::STATE_CANCELED, $response['result']);
                $this->messageManager->addErrorMessage($response['result']);
                $this->response->setRedirect($this->_urlBuilder->getUrl('checkout/onepage/failure'));
            }
        }

        setcookie("idpay_order_id", "", time() - 3600, "/");
        setcookie('idpay_hash', "" , time()-3600, '/');
        return $response;
    }

    public function callback(): array
    {
      return $this->doCallback();
    }

    public function addTransaction($order, $txnId, $paymentData = []): int
    {
        $payment = $order->getPayment();
        $payment->setMethod('idpay');
        $payment->setLastTransId($txnId);
        $payment->setTransactionId($txnId);
        $payment->setIsTransactionClosed(0);
        $payment->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData]);
        $payment->setParentTransactionId(null);

        // Prepare transaction
        $transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setFailSafe(true)
            ->setTransactionId($txnId)
            ->setAdditionalInformation([Transaction::RAW_DETAILS => (array) $paymentData])
            ->build(Transaction::TYPE_CAPTURE);

        // Add transaction to payment
        $payment->addTransactionCommentsToOrder($transaction, __('The authorized TransactionId is %1.', $txnId));
        $payment->setParentTransactionId(null);

        // Save payment, transaction and order
        $payment->save();
        $order->save();
        $transaction->save()->close();

        return  $transaction->getTransactionId();
    }

    public function getStatus($messageNumber): string
    {
        switch ($messageNumber) {
            case 1:
                return 'پرداخت انجام نشده است';
            case 2:
                return 'پرداخت ناموفق بوده است';
            case 3:
                return 'خطا رخ داده است';
            case 4:
                return 'بلوکه شده';
            case 5:
                return 'برگشت به پرداخت کننده';
            case 6:
                return 'برگشت خورده سیستمی';
            case 7:
                return 'انصراف از پرداخت';
            case 8:
                return 'به درگاه پرداخت منتقل شد';
            case 10:
                return 'در انتظار تایید پرداخت';
            case 100:
                return 'پرداخت تایید شده است';
            case 101:
                return 'پرداخت قبلا تایید شده است';
            case 200:
                return 'به دریافت کننده واریز شد';
        }
        return 'خطا رخ داده است';
    }
}
