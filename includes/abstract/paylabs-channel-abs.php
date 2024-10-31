<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!function_exists('download_url')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

/**
 * Paylabs_Channel Class
 * Abstract class prototype to be extended by sub gateway separated channel options.
 * This to allow different payment option to have own payment option button.
 */
abstract class Paylabs_Channel extends WC_Payment_Gateway
{
    const STATUS_PENDING = '01';
    const STATUS_SUCCESS = '02';
    const STATUS_FAILED  = '09';


    public $sub_method_params;
    public $sub_icon;
    public $payment_link;
    public $inquiry_link;

    public $api_url = 'https://pay.paylabs.co.id/payment/v2';
    public $api_test_url = 'https://sit-pay.paylabs.co.id/payment/v2';


    /**
     * Constructor
     */
    function __construct()
    {
        // $this->id = ''; // override me. sample: 'paylabs_danamon_va';
        // this->method_title = ''; override me. sample: 'Danamon VA';
        // $this->sub_method_params =  []; // override me. sample: ['danamon_va'];
        // $this->sub_icon =  []; // override me. sample: 'danamon_va.png';
        // $this->payment_link = ''; override me. sample '/h5/createLink'
        // override above values when extending this class

        $this->init_form_fields();
        $this->init_settings();
        $this->icon = '';

        if (isset($this->settings['enable_icon']) && $this->settings['enable_icon'] == 'yes') {
            if (isset($this->settings['custom_icon']) && !empty($this->settings['custom_icon'])) {
                $this->icon = $this->settings['custom_icon'];
            } else {
                $this->icon = plugins_url('', __FILE__) . '/../../asset/' . $this->sub_icon;
            }
        }

        // in case you need a custom credit card form
        $this->has_fields = false;

        //Macam-macam method yang bisa dilakukan
        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        // $this->init_form_fields();

        // Load the settings.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');

        //get main plugin "paylabs_h5" plugin options
        $main_options = get_option('woocommerce_paylabs_h5_settings', array());
        $this->testmode = ('yes' == (isset($main_options['testmode']) ? $main_options['testmode'] : 'yes'));
        $this->debugMode = ('yes' == (isset($main_options['debugMode']) ? $main_options['debugMode'] : 'no'));
        $this->merchant_id = isset($main_options['merchant_id']) ? $main_options['merchant_id'] : '';
        $this->new_order_status = isset($main_options['new_order_status']) ? $main_options['new_order_status'] : 'wc-processing';
        if ($this->testmode != "yes") {
            $this->private_key = isset($main_options['private_key']) ? $main_options['private_key'] : '';
            $this->public_key = isset($main_options['public_key']) ? $main_options['public_key'] : '';
        } else {
            $this->private_key = isset($main_options['private_key_sandbox']) ? $main_options['private_key_sandbox'] : '';
            $this->public_key = isset($main_options['public_key_sandbox']) ? $main_options['public_key_sandbox'] : '';
        }
        // $this->dateTime = date("Y-m-d") . "T" . date("H:i:s.B") . "+07:00";
        $this->dateTime = current_datetime()->format('Y-m-d') . "T" . current_datetime()->format('H:i:s.B') . "+07:00";

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // We need custom JavaScript to obtain a token
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        //Register webhook for returnUrl
        //http://xxxxx.com/wc-api/paylabs-notify/
        add_action('woocommerce_api_paylabs-notify', array($this, 'webhook_notify_from_paylabs'));

        // //http://xxxxx.com/wc-api/paylabs-callback/
        // add_action('woocommerce_api_paylabs-callback', array( $this, 'webhook_callback_from_paylabs' ) );

        add_action('woocommerce_thankyou', array($this, 'inquiry_thank_you_page'));
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Enable/Disable',
                'label'       => 'Enable or Disable ' . $this->method_title,
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Title',
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => $this->method_title,
                'desc_tip'    => true,
            ),
            'enable_icon' => array(
                'title' => 'Payment Icon',
                'label' => 'Enable Icon',
                'type' => 'checkbox',
                'description' => '<img src="' . plugins_url('../asset/' . $this->sub_icon, dirname(__FILE__)) . '" style="height:100%;max-height:40px !important" />',
                'default' => 'no',
            ),
            'custom_icon' => array(
                'title'       => 'Custom Url Icon',
                'type'        => 'url',
                'description' => 'The url must have a png extension.',
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => $this->method_description,
            )
        );
    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
    public function payment_fields()
    {
        echo $this->description;
    }

    /*
 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
 */
    public function payment_scripts()
    {
    }

    /*
  * Fields validation
 */
    public function validate_fields()
    {
    }

    private static function generate_hash($jsonBody, $private_key, $endpoint, $orderTime)
    {
        $shaJson  = strtolower(hash('sha256', $jsonBody));
        $signatureBefore = "POST:" . $endpoint . ":" . $shaJson . ":" . $orderTime;
        $binary_signature = "";

        $algo = OPENSSL_ALGO_SHA256;
        openssl_sign($signatureBefore, $binary_signature, $private_key, $algo);

        $sign = base64_encode($binary_signature);
        return $sign;
    }

    private static function verify_sign($dataToSign, $sign, $dateTime, $publicKey)
    {
        $binary_signature = base64_decode($sign);
        // $dataToSign = json_encode(json_decode($dataToSign), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $shaJson  = strtolower(hash('sha256', $dataToSign));
        $signatureAfter = "POST:/:" . $shaJson . ":" . $dateTime;

        $publicKey = openssl_pkey_get_public($publicKey);
        if ($publicKey === false) {
            die("Error loading public key");
        }

        $algo =  OPENSSL_ALGO_SHA256;
        $verificationResult = openssl_verify($signatureAfter, $binary_signature, $publicKey, $algo);

        if ($verificationResult === 1) {
            return true;
        }
        return false;
    }

    public function get_api_baseurl()
    {
        if (true === $this->testmode) {
            return $this->api_test_url;
        }

        return $this->api_url;
    }


    protected abstract function get_payment_params($order);
    protected abstract function get_after_process_payment_url($data);

    /**
     * The function to execute API for generate the payment option
     * This will create order in Paylabs.
     */
    public function execute_payment($order)
    {
        $params = $order->get_data();
        $signature = '';

        $data = $this->get_payment_params($params);
        $data['requestId'] .= "-" . date("ymd");
        $data['merchantTradeNo'] .= "-" . date("ymdHis");

        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $signature = self::generate_hash($jsonBody, $this->private_key, "/payment/v2" . $this->payment_link, $this->dateTime);

        $result =  wp_remote_post($this->get_api_baseurl() . $this->payment_link, [
            'body' => $jsonBody,
            'headers' => array(
                'X-TIMESTAMP' =>  $this->dateTime,
                'X-SIGNATURE' =>  $signature,
                'X-PARTNER-ID' =>  $this->merchant_id,
                'X-REQUEST-ID' =>  $data['requestId'],
                'Content-Type' => 'application/json;charset=utf-8'
            )
        ]);

        //store the paymentType into the post-meta so that we can retrieve later. like in email and so on
        update_post_meta($params['id'], 'paymentType', $data['paymentType']);

        return $result;
    }


    /**
     * The function to execute API for inquiry
     */
    public function execute_inquiry($params)
    {
        $params['paymentType'] = get_post_meta($params['merchantTradeNo'], 'paymentType', true);
        $jsonBody = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = self::generate_hash($jsonBody, $this->private_key, "/payment/v2" . $this->inquiry_link, $this->dateTime);

        $result =  wp_remote_post($this->get_api_baseurl() . $this->inquiry_link, [
            'body' => $jsonBody,
            'headers' => array(
                'X-TIMESTAMP' =>  $this->dateTime,
                'X-SIGNATURE' =>  $signature,
                'X-PARTNER-ID' =>  $this->merchant_id,
                'X-REQUEST-ID' =>  $params['requestId'],
                'Content-Type' => 'application/json;charset=utf-8'
            )
        ]);

        return $result;
    }




    /*
 * We're processing the payments here. This is Woocommerce plugin defined function
 */
    public function process_payment($order_id)
    {
        global $woocommerce;

        // we need it to get any order detailes
        $order = wc_get_order($order_id);

        /*
     * Your API interaction could be built with wp_remote_post()
      */
        $response = $this->execute_payment($order);

        $body = json_decode($response['body']);

        //If this has error then we should return error
        if (isset($body->errCodeDes) && !empty($body->errCodeDes)) {
            error_log("Payment Error : " . $body->errCodeDes);
            wc_add_notice(__('Payment error:', 'woothemes') . $body->errCodeDes, 'error');
            $order->add_order_note('Error: ' . esc_html(strval($body->errCodeDes)));
            return array(
                'result'   => 'fail',
                'redirect' => ''
            );
        }

        //store va number from paylabs
        if (isset($body->vaCode)) {
            update_post_meta($order_id, 'va_number', $body->vaCode);
        } elseif (isset($body->qrCode)) {
            $file_url = $body->qrisUrl;
            $tmp_file = download_url($file_url);
            // Mendapatkan direktori upload WordPress
            $upload_dir = wp_upload_dir();

            // Sets file final destination.
            $filepath = $upload_dir['path'] . '/' . $body->platformTradeNo . '.png';

            // Copies the file to the final destination and deletes temporary file.
            copy($tmp_file, $filepath);
            @unlink($tmp_file);

            update_post_meta($order_id, 'qr_code', $body->qrCode);
            update_post_meta($order_id, 'qr_code_image', '/wp-content/uploads' . $upload_dir['subdir'] . '/' . $body->platformTradeNo . '.png');
        } elseif (isset($body->paymentActions->mobilePayUrl)) {
            update_post_meta($order_id, 'ewallet_link', $body->paymentActions->mobilePayUrl);
        }

        //clear cart
        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order) //$this->get_after_process_payment_url($response)
        );
    }

    /*
 * Get the callback from API
 */
    public function webhook_notify_from_paylabs()
    {

        //validate the signature 
        $headers = $_SERVER;
        $httpSign = isset($headers['HTTP_X_SIGNATURE']) ? $headers['HTTP_X_SIGNATURE'] : '';
        $dateTime = isset($headers['HTTP_X_TIMESTAMP']) ? $headers['HTTP_X_TIMESTAMP'] : '';
        $rawdata = file_get_contents('php://input');
        $data = json_decode($rawdata, true);

        //khusus data di amount selalu mengembalikan nilai .00
        $data['amount'] = intval($data['amount']) . '.00';

        $verifySign = self::verify_sign($rawdata, $httpSign, $dateTime, $this->public_key);

        if ($verifySign === true && $data['errCode'] == '0' && $data['status'] == '02') {
            $splitTradeNo = explode("-", $data['merchantTradeNo']);
            $orderId = $splitTradeNo[0];
            $order = wc_get_order($orderId);
            // $order->payment_complete();
            $order->update_status($this->new_order_status);
            // $order->reduce_order_stock(); //deprecated v3.0
            $request_id = $orderId . "-" . $data['successTime'];
            // Create the response body as an associative array
            $response = array(
                "merchantId" => $data['merchantId'],
                "requestId" => $request_id,
                "errCode" => "0"
            );

            $signature = self::generate_hash(json_encode($response), $this->private_key, "/", $this->dateTime);

            // Set HTTP response headers
            header("Content-Type: application/json;charset=utf-8");
            header("X-TIMESTAMP: " . $this->dateTime);
            header("X-SIGNATURE: " . $signature);
            header("X-PARTNER-ID: " . $data['merchantId']);
            header("X-REQUEST-ID: " . $request_id);

            // Encode the response as JSON and output it
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            die();
        }
        if ($this->debugMode == "yes") {
            var_dump($this->public_key);
            var_dump(json_encode($headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            var_dump($rawdata);
        }
        echo 'FAILED';
        die();
    }


    function inquiry_thank_you_page($order_id)
    {
?>
        <script>
            window.setTimeout(function() {
                window.location.reload();
            }, 30000);
        </script>
<?php
    }
}
