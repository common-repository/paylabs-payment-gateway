<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Paylabs Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_Paylabs_Blocks_Support extends AbstractPaymentMethodType
{
	/**
	 * The gateway instance.
	 *
	 * @var KL_Paylabs_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Constructor to set the gateway ID.
	 *
	 * @param string $gateway_id
	 */
	public function __construct($gateway_id)
	{
		$this->name = $gateway_id;
		$this->initialize();
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_paylabs_settings', []);
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[$this->name];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
	{
		wp_register_script(
			'wc-paylabs-payments-blocks',
			plugins_url('../assets/js/frontend/blocks.js', __FILE__),
			[
				'react', 'wc-blocks-registry', 'wp-element', 'wp-html-entities', 'wp-i18n',
			],
			null,
			true
		);

		return ['wc-paylabs-payments-blocks'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		return [
			'title'       => $this->get_setting('title'),
			'description' => $this->get_setting('description'),
			'supports'    => array_filter($this->gateway->supports, [$this->gateway, 'supports'])
		];
	}
}
