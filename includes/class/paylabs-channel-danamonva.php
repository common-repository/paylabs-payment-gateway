<?php
if (!defined('ABSPATH')) {
   exit;
}

class Paylabs_Channel_DanamonVA extends Paylabs_Channel
{

   /**
    * Constructor
    */
   function __construct()
   {
      $this->id = 'paylabs_danamon_va';
      $this->sub_method_title = 'Danamon Virtual Account';
      $this->sub_icon =  'danamonva.png';
      $this->payment_link = '/va/create';
      $this->inquiry_link = '/va/query';
      $this->method_title = 'Paylabs - Danamon VA';
      $this->method_description = 'Paylabs Danamon VA';

      //Register webhook for VA result page
      //http://xxxxx.com/wc-api/paylabs-va/
      //  add_action('woocommerce_api_paylabs-danamon-va', array( $this, 'webhook_va_page' ) );

      parent::__construct();
   }

   function get_payment_params($params)
   {

      if (empty($params['billing']['phone']) || strlen($params['billing']['phone']) == 0) {
         $params['billing']['phone'] = '000000000';
      }

      return [
         'requestId' => $this->merchant_id . '-' . $params['id'],
         'merchantId' => $this->merchant_id,
         'paymentType' => 'DanamonVA',
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
      return get_site_url(null, '/') . '?paylabs-mod=danamonva&va=' . $body['vaCode'] . '&id=' . $body['merchantTradeNo'] . '&rq=' . $body['requestId'];
   }
}
