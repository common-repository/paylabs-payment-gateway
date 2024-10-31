<?php
if (! defined('ABSPATH')) {
  exit;
}

class Paylabs_Channel_DanaBalance extends Paylabs_Channel{
    
/**
 * Constructor
 */
 function __construct() {
    $this->id = 'paylabs_dana_balance';
    $this->sub_method_title = 'Dana Balance';
    $this->sub_icon =  'danabalance.png'; 
    $this->payment_link = '/ewallet/create';
    $this->inquiry_link = '/ewallet/query';
    $this->method_title = 'Paylabs - Dana Balance';
	 $this->method_description = 'Paylabs Dana Balance';

    //Register webhook for QRIS result page
    //http://xxxxx.com/wc-api/paylabs-dana/
   //  add_action('woocommerce_api_paylabs-dana', array( $this, 'webhook_dana_page' ) );			
	
    parent::__construct();
 }

 function get_payment_params($params)
 {

   if(empty($params['billing']['phone']) || strlen($params['billing']['phone'])==0){
        $params['billing']['phone'] = '000000000';   
    }

    return [
        'requestId'=> $this->merchant_id.'-'.$params['id'],
        'merchantId' => $this->merchant_id,
        'paymentType' => 'DANABALANCE',
        'merchantTradeNo'=> $params['id'],
        'amount'=> number_format($params['total'], 2, '.', ''),
        'phoneNumber'=>$params['billing']['phone'],
        'payer'=>trim($params['billing']['first_name'].' '.$params['billing']['last_name']),
        'productName'=> paylabs_get_order_string($params['id']),
        'paymentParams'=> [ 'redirectUrl' => get_site_url(null,'/')],
        'notifyUrl'=> get_site_url(null,'/')."?wc-api=paylabs-notify",
    ];
}

 function get_after_process_payment_url($response){
   $body = json_decode($response['body'], true);
   return $body['paymentActions']['mobilePayUrl'];
}

}
