<?php
if (! defined('ABSPATH')) {
  exit;
}

class Paylabs_Channel_ShopeeBalance extends Paylabs_Channel{
    
/**
 * Constructor
 */
 function __construct() {
    $this->id = 'paylabs_shopee_balance';
    $this->sub_method_title = 'Shopee Balance';
    $this->sub_icon =  'shopeebalance.png'; 
    $this->payment_link = '/ewallet/create';
    $this->inquiry_link = '/ewallet/query';
    $this->method_title = 'Paylabs - Shopee Balance';
	 $this->method_description = 'Paylabs Shopee Balance';

    //Register webhook for QRIS result page
    //http://xxxxx.com/wc-api/paylabs-shopee/
   //  add_action('woocommerce_api_paylabs-shopee', array( $this, 'webhook_shopee_page' ) );			
	
    parent::__construct();
 }

 function get_payment_params($params)
 {
   $paymentParams = new stdClass;
   $paymentParams->redirectUrl = $this->get_done_process_payment_url([
      'id'=> $params['id']
   ]);

   if(empty($params['billing']['phone']) || strlen($params['billing']['phone'])==0){
        $params['billing']['phone'] = '000000000';   
    }

    return [
        'requestId'=> $this->merchant_id.'-'.$params['id'],
        'merchantId' => $this->merchant_id,
        'paymentType' => 'SHOPEEBALANCE',
        'merchantTradeNo'=> $params['id'],
        'amount'=> number_format($params['total'], 2, '.', ''),
        'phoneNumber'=>$params['billing']['phone'],
        'payer'=>trim($params['billing']['first_name'].' '.$params['billing']['last_name']),
        'productName'=> paylabs_get_order_string($params['id']),
        'paymentParams'=>$paymentParams,
        'notifyUrl'=> get_site_url(null,'/')."?wc-api=paylabs-notify",
    ];
}

 function get_after_process_payment_url($response){
   $body = json_decode($response['body'], true);
   return $body['paymentActions']['mobilePayUrl'];
}

function get_done_process_payment_url($param){
   return get_site_url(null,'/').'?paylabs-mod=shopeebalance&id='.$param['id'];
}

// function webhook_shopee_page()
// {
//     $order_id = intval($_GET['id']);
//     $order = wc_get_order( $order_id );
//     $picture = $this->icon;

//     //get status inquiry
//     $params = [
//         'requestId'=>$request_id,
//         'merchantId'=>$this->merchant_id,
//         'merchantTradeNo'=>$order_id,
//     ];

//     $inquiryResponse = $this->execute_inquiry($params);
//     $result = json_decode($inquiryResponse['body'], true);

//     require_once dirname( __FILE__ ) . '/../../page/dana.php';
//     die();	    
// }


// function get_page_content($page)
// {
//     $order_id = intval($_GET['id']);
//     $order = wc_get_order( $order_id );
//     $picture = $this->icon;

//     //get status inquiry
//     $params = [
//         'requestId'=>$request_id,
//         'merchantId'=>$this->merchant_id,
//         'merchantTradeNo'=>$order_id,
//     ];

//     $inquiryResponse = $this->execute_inquiry($params);
//     $result = json_decode($inquiryResponse['body'], true);

//     ob_start();
//     require_once $page;
//     $content = ob_get_clean();

//     return $content;
// }


}
