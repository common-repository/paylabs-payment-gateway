<?php
if (!defined('ABSPATH')) {
	exit;
}

class Paylabs_Channel_H5 extends WC_Payment_Gateway
{

	public $api_url = 'https://pay.paylabs.co.id/payment/v2';
	public $api_test_url = 'https://sit-pay.paylabs.co.id/payment/v2';

	/**
	 * Class constructor, more about it in Step 3
	 */
	public function __construct()
	{

		$this->id = 'paylabs_h5'; // payment gateway plugin ID. 
		$this->sub_icon = apply_filters('woocommerce_gateway_icon', '' . plugins_url('', __FILE__) . '/../../asset/logo-2.png'); // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields = false; // in case you need a custom credit card form
		$this->method_title = 'Paylabs Payment Gateway';
		$this->method_description = 'Online Payment (Bank Transfer, Virtual Account, QRIS, E-Money)'; // will be displayed on the options page

		//Macam-macam method yang bisa dilakukan
		$this->supports = array(
			'products'
		);

		// Method with all the options fields
		$this->init_form_fields();

		// Load the settings.
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled = $this->get_option('enabled');
		$this->testmode = 'yes' === $this->get_option('testmode');
		$this->enable_icon = 'yes' === $this->get_option('enable_icon');
		$this->new_order_status = $this->get_option('new_order_status') ? $this->get_option('new_order_status') : 'wc-processing';
		$this->custom_icon = $this->get_option('custom_icon');
		$this->merchant_id = $this->get_option('merchant_id');
		$this->debugMode = $this->get_option('debugMode');
		$this->private_key = $this->testmode == "yes" ? $this->get_option('private_key_sandbox') : $this->get_option('private_key');
		$this->public_key = $this->testmode == "yes" ? $this->get_option('public_key_sandbox') : $this->get_option('public_key');
		// $this->dateTime = date("Y-m-d") . "T" . date("H:i:s.B") . "+07:00";
		$this->dateTime = current_datetime()->format('Y-m-d') . "T" . current_datetime()->format('H:i:s.B') . "+07:00";

		$this->icon = '';
		if ($this->enable_icon == 'yes') {
			if (!empty($this->custom_icon)) {
				$this->icon = $this->custom_icon;
			} else {
				$this->icon = $this->sub_icon;
			}
		}

		// This action hook saves the settings
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		// We need custom JavaScript to obtain a token
		add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

		//Register webhook for returnUrl
		//http://xxxxx.com/wc-api/paylabs-notify/
		add_action('woocommerce_api_paylabs-h5-notify', array($this, 'webhook_notify_h5_from_paylabs'));

		//http://xxxxx.com/wc-api/paylabs-callback/
		add_action('woocommerce_api_paylabs-callback', array($this, 'webhook_callback_from_paylabs'));
	}

	/**
	 * Plugin options, we deal with it in Step 3 too
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title'       => 'Enable/Disable Paylabs Payment Page (Paylabs H5)',
				'label'       => 'Enable Paylabs Payment Page, so your customer can choose payment options on Paylabs page',
				'type'        => 'checkbox',
				'description' => 'Activate this option if you wish to use Paylabs Payment Gateway page. If you don\'t wish to use payment link option but use other Paylabs payment option you can keep this unchecked.',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => 'The title of Paylabs Payment Page method, which the user sees during checkout.',
				'default'     => 'Secure Payment (Bank Transfer, Virtual Account, QRIS)',
				'desc_tip'    => true,
			),
			'enable_icon' => array(
				'title' => 'Payment Icon',
				'label' => 'Enable Icon',
				'type' => 'checkbox',
				'description' => '<img src="' . $this->sub_icon . '" style="height:100%;max-height:40px !important" />',
				'default' => 'no',
			),
			'custom_icon' => array(
				'title'       => 'Custom Url Icon',
				'type'        => 'url',
				'description' => 'The url must have a png extension.',
			),
			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'The description of Paylabs Payment Page method, which the user sees during checkout.',
				'default'     => 'Various option payments',
			),
			'send_invoice' => array(
				'title'       => 'Send Invoice',
				'type'        => 'checkbox',
				'description' => 'Check this box, and it will send invoice email containing the payment link. Only support on Paylabs Payment Gateway method.',
				'default'     => 'no',
			),
			'new_order_status' => array(
				'title'       => 'New Order Status',
				'type'        => 'select',
				'options'     => wc_get_order_statuses(), // Get WooCommerce order statuses
				'description' => 'what status will be changed to after payment occurs.',
				'default'     => 'wc-processing', // Default order status
			),
			'testmode' => array(
				'title'       => 'Sandbox Mode (Testing Mode)',
				'label'       => 'Enable Test Mode',
				'type'        => 'checkbox',
				'description' => 'If checked, it will place the payment gateway in test mode using test API keys.',
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'merchant_id' => array(
				'title'       => 'Merchant ID*',
				'type'        => 'text',
				'description' => 'This Merchant ID affected to all payment options. This field is mandatory.',
			),
			'public_key' => array(
				'title'       => 'Paylabs Public Key*',
				'type'        => 'textarea',
				'description' => 'The public key provided by Paylabs.',
			),
			'private_key' => array(
				'title'       => 'Merchant Private Key*',
				'type'        => 'textarea',
				'description' => "The private key that generate by yourself. Please don't give this key to anyone.",
			),
			'public_key_sandbox' => array(
				'title'       => 'Paylabs Public Key* (Sandbox)',
				'type'        => 'textarea',
				'description' => 'The public key provided by Paylabs.',
			),
			'private_key_sandbox' => array(
				'title'       => 'Merchant Private Key* (Sandbox)',
				'type'        => 'textarea',
				'description' => "The private key that generate by yourself. Please don't give this key to anyone.",
			),
			'debugMode' => array(
				'title'       => 'Debug Mode',
				'label'       => 'Enable Debug Mode',
				'type'        => 'checkbox',
				'description' => 'If checked, it will print data for debug.',
				'default'     => 'no',
				'desc_tip'    => true,
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

		// $dataToSign = json_encode($dataToSign, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

	private function get_api_baseurl()
	{
		if (true === $this->testmode) {
			return $this->api_test_url;
		}

		return $this->api_url;
	}

	private function execute_payment($order)
	{
		$params = $order->get_data();
		$signature = '';

		if (empty($params['billing']['phone']) || strlen($params['billing']['phone']) == 0) {
			$params['billing']['phone'] = '00000';
		}

		$requestId = $this->merchant_id . '-' . $params['id'] . "-" . date("ymd");
		$tradeNo = $params['id'] . "-" . date("ymdHis");

		$data = [
			'requestId' => $requestId,
			'merchantId' => $this->merchant_id,
			'merchantTradeNo' => $tradeNo,
			'amount' => number_format($params['total'], 2, '.', ''),
			'phoneNumber' => $params['billing']['phone'],
			'productName' => paylabs_get_order_string($params['id']),
			'redirectUrl' => get_site_url(null, '/') . "?wc-api=paylabs-callback&id=" . $params['id'],
			'notifyUrl' => get_site_url(null, '/') . "?wc-api=paylabs-h5-notify"
		];
		$jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		$signature = self::generate_hash($jsonBody, $this->private_key, "/payment/v2/h5/createLink", $this->dateTime);

		$result = wp_remote_post($this->get_api_baseurl() . '/h5/createLink', array(
			'body'    => $jsonBody,
			'headers' => array(
				'X-TIMESTAMP' =>  $this->dateTime,
				'X-SIGNATURE' =>  $signature,
				'X-PARTNER-ID' =>  $this->merchant_id,
				'X-REQUEST-ID' =>  $requestId,
				'Content-Type' => 'application/json;charset=utf-8'
			)
		));

		return $result;
	}

	/*
		* We're processing the payments here, everything about it is in Step 5
		*/
	public function process_payment($order_id)
	{
		global $woocommerce;

		// we need it to get any order detailes
		$order = wc_get_order($order_id);
		/*
			* Your API interaction could be built with wp_remote_post()
			*/
		$paymentLink = get_post_meta($order_id, 'h5_link', true);
		if (!$paymentLink) {

			$response = $this->execute_payment($order);

			$body = json_decode($response['body']);
			error_log("response");
			error_log($response['body']);
			//If this has error then we should return error
			if (!empty($body->errCodeDes)) {
				error_log("Payment Error : " . $body->errCodeDes);
				wc_add_notice(__('Payment error:', 'woothemes') . $body->errCodeDes, 'error');
				return array(
					'result'   => 'fail',
					'redirect' => ''
				);
			}
			$paymentLink = $body->url;

			//store the link into the wp-options so that we can retrieve later. like in email and so on
			add_post_meta($order_id, 'h5_link', $paymentLink);

			//sending email if activating 'send_invoice' options
			if ($this->get_option('send_invoice') === 'yes') {
				//send the email
				paylabs_send_invoice($order_id);
			}
		}

		//clear cart
		WC()->cart->empty_cart();

		return array(
			'result' => 'success',
			'redirect' => $paymentLink
		);
	}

	/*
		* Get the callback from API
		*/
	public function webhook_notify_h5_from_paylabs()
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

	public function webhook_callback_from_paylabs()
	{
		$order_id = intval($_GET['id']);
		$order = wc_get_order($order_id);
		$order_key = $order->get_order_key();
		$url = get_site_url(null, '/') . 'checkout/order-received/' . $order_id . '/?key=' . $order_key;
		wp_safe_redirect($url);
		die();
	}
}
