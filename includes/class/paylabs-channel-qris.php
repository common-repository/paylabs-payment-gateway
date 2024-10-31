<?php
if (!defined('ABSPATH')) {
    exit;
}

class Paylabs_Channel_Qris extends Paylabs_Channel
{

    /**
     * Constructor
     */
    function __construct()
    {
        $this->id = 'paylabs_qris';
        $this->sub_method_title = 'QRIS';
        $this->sub_icon =  'qris.png';
        $this->payment_link = '/qris/create';
        $this->inquiry_link = '/qris/query';
        $this->method_title = 'Paylabs - QRIS';
        $this->method_description = 'Paylabs QRIS';

        //Register webhook for QRIS result page
        //http://xxxxx.com/wc-api/paylabs-qris/
        //add_action('woocommerce_api_paylabs-qris', array( $this, 'webhook_qris_page' ) );			

        parent::__construct();
    }

    public function webhook_qris_page()
    {
        $qrCode = $_GET['qris'];

        if (!preg_match('/[0-9a-zA-Z\.\ ]+/', $qrCode)) {
            die("Incorrect QRIS code format. Please contact cs@paylabs.co.id for support");
            return false;
        }

        if (!preg_match('/ID[0-9]+/', $qrCode)) {
            die("Incorrect NMID code format. Please contact cs@paylabs.co.id for support");
            return false;
        }

        $nmid = $_GET['nmid'];
        $expiredTime = intval($_GET['expiredTime']);
        $order_id = intval($_GET['id']);
        $request_id = intval($_GET['rq']);
        $order = wc_get_order($order_id);
        $picture = $this->icon;

        //get status inquiry
        $params = [
            'requestId' => $request_id,
            'merchantId' => $this->merchant_id,
            'merchantTradeNo' => $order_id,
        ];
        $inquiryResponse = $this->execute_inquiry($params);
        $result = json_decode($inquiryResponse['body'], true);

        require_once dirname(__FILE__) . '/../../page/qris.php';
        die();
    }

    public function get_page_content($page)
    {
        $qrCode = $_GET['qris'];

        if (!$this->testmode && !preg_match('/[0-9a-zA-Z\.\ ]+/', $qrCode)) {
            die("Incorrect QRIS code format. Please contact cs@paylabs.co.id for support");
            return false;
        }

        if (!$this->testmode && !preg_match('/ID[0-9]+/', $qrCode)) {
            die("Incorrect NMID code format. Please contact cs@paylabs.co.id for support");
            return false;
        }

        $nmid = $_GET['nmid'];
        $expiredTime = intval($_GET['expiredTime']);
        $order_id = intval($_GET['id']);
        $request_id = intval($_GET['rq']);
        $order = wc_get_order($order_id);
        $picture = $this->icon;

        //get status inquiry
        $params = [
            'requestId' => $request_id,
            'merchantId' => $this->merchant_id,
            'merchantTradeNo' => $order_id,
        ];
        $inquiryResponse = $this->execute_inquiry($params);
        $result = json_decode($inquiryResponse['body'], true);

        ob_start();
        require_once $page;
        $content = ob_get_clean();

        return $content;
    }

    function get_payment_params($params)
    {

        if (empty($params['billing']['phone']) || strlen($params['billing']['phone']) == 0) {
            $params['billing']['phone'] = '000000000';
        }

        return [
            'requestId' => $this->merchant_id . '-' . $params['id'],
            'merchantId' => $this->merchant_id,
            'paymentType' => 'QRIS',
            'merchantTradeNo' => $params['id'],
            'amount' => number_format($params['total'], 2, '.', ''),
            'phoneNumber' => $params['billing']['phone'],
            'payer' => trim($params['billing']['first_name'] . ' ' . $params['billing']['last_name']),
            'productName' => paylabs_get_order_string($params['id']),
            'notifyUrl' => get_site_url(null, '/') . "?wc-api=paylabs-notify",
        ];
    }

    function get_after_process_payment_url($response)
    {
        $body = json_decode($response['body'], true);
        $qrisCode = $body['qrCode'];
        $nmid = $body['nmid'];
        $id = $body['merchantTradeNo'];
        $expiredTime = $body['expiredTime'];
        $requestId = $body['requestId'];
        return get_site_url(null, '/') . "?paylabs-mod=qris&qris=$qrisCode&nmid=$nmid&expiredTime=$expiredTime&id=$id&rq=$requestId";
    }
}
