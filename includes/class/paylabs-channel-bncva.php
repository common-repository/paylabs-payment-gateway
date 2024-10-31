<?php
if (!defined('ABSPATH')) {
    exit;
}

class Paylabs_Channel_BNCVA extends Paylabs_Channel
{

    /**
     * Constructor
     */
    function __construct()
    {
        $this->id = 'paylabs_bnc_va';
        $this->sub_method_title = 'BNC Virtual Account';
        $this->sub_icon =  'bncva.png';
        $this->payment_link = '/va/create';
        $this->inquiry_link = '/va/query';
        $this->method_title = 'Paylabs - BNC VA';
        $this->method_description = 'Paylabs BNC VA';

        //Register webhook for VA result page
        //http://xxxxx.com/wc-api/paylabs-va/
        // add_action('woocommerce_api_paylabs-bnc-va', array( $this, 'webhook_va_page' ) );

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
            'paymentType' => 'BNCVA',
            'merchantTradeNo' => $params['id'],
            'amount' => number_format($params['total'], 2, '.', ''),
            'phoneNumber' => $params['billing']['phone'],
            'payer' => trim($params['billing']['first_name'] . ' ' . $params['billing']['last_name']),
            'productName' => paylabs_get_order_string($params['id']),
            'notifyUrl' => get_site_url(null, '/') . "?wc-api=paylabs-notify"
        ];
    }

    function get_after_process_payment_url($response)
    {
        $body = json_decode($response['body'], true);
        return get_site_url(null, '/') . '?paylabs-mod=bncva&va=' . $body['vaCode'] . '&id=' . $body['merchantTradeNo'] . '&rq=' . $body['requestId'];
    }

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
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => $this->method_description,
            )
        );
    }
}
