<?php
/*
 * Plugin Name: Paylabs Payment Gateway
 * Plugin URI: https://www.paylabs.co.id/
 * Description: Paylabs' Payment Gateway Plugin for Woocommerce
 * Author: Paylabs IT Team
 * Author URI: https://www.paylabs.co.id
 * Version: 1.1.2
 */

function paylabs_is_woocommerce_active()
{

	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		return true;
	} else {
		return false;
	}
}

if (paylabs_is_woocommerce_active()) {
	add_filter('woocommerce_payment_gateways', 'paylabs_add_gateway_class');
}

function paylabs_add_gateway_class($gateways)
{
	$paylabs = [
		'Paylabs_Channel_H5',
		'Paylabs_Channel_MaybankVA',
		'Paylabs_Channel_DanamonVA',
		'Paylabs_Channel_SinarmasVA',
		'Paylabs_Channel_BSIVA',
		'Paylabs_Channel_BNCVA',
		'Paylabs_Channel_INAVA',
		'Paylabs_Channel_BNIVA',
		'Paylabs_Channel_MandiriVA',
		'Paylabs_Channel_PermataVA',
		'Paylabs_Channel_BTNVA',
		'Paylabs_Channel_CIMBVA',
		'Paylabs_Channel_MuamalatVA',
		'Paylabs_Channel_BCAVA',
		'Paylabs_Channel_BRIVA',
		'Paylabs_Channel_Qris',
		'Paylabs_Channel_DanaBalance',
		'Paylabs_Channel_OvoBalance',
		'Paylabs_Channel_LinkajaBalance',
		'Paylabs_Channel_ShopeeBalance',
		'Paylabs_Channel_GopayBalance',
	];
	return array_merge($gateways, $paylabs);
}

if (paylabs_is_woocommerce_active()) {
	add_action('plugins_loaded', 'paylabs_init_gateway_class');
}

//You need to add class name here whenever there is new class
function paylabs_init_gateway_class()
{
	require_once dirname(__FILE__) . '/includes/abstract/paylabs-channel-abs.php';
	foreach (glob(dirname(__FILE__) . '/includes/class/*.php') as $filename) {
		require_once $filename;
	}
}

//You need to add class name here too
function paylabs_get_page_template($modulename)
{
	switch ($modulename) {
		case 'maybankva':
			$object = new Paylabs_Channel_MaybankVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'danamonva':
			$object = new Paylabs_Channel_DanamonVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'sinarmasva':
			$object = new Paylabs_Channel_SinarmasVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'bsiva':
			$object = new Paylabs_Channel_BSIVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'bncva':
			$object = new Paylabs_Channel_BNCVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'inava':
			$object = new Paylabs_Channel_INAVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'bniva':
			$object = new Paylabs_Channel_BNIVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'mandiriva':
			$object = new Paylabs_Channel_MandiriVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'permatava':
			$object = new Paylabs_Channel_PermataVA;
			return [
				'page_name' => 'va',
				'template_name' => 'pay',
				'object' => $object
			];
			break;
		case 'danabalance':
			return [
				'page_name' => 'dana',
				'template_name' => 'pay',
				'object' => new Paylabs_Channel_DanaBalance
			];
			break;

		case 'qris':
			return [
				'page_name' => 'qris',
				'template_name' => 'pay',
				'object' => new Paylabs_Channel_Qris
			];
			break;
	}

	//if no matching module name, then just return empty string
	return [
		'page_name' => '',
		'template_name' => '',
		'object' => null
	];
}

//Query vars for Paylabs Module
add_filter('query_vars', function ($query_vars) {
	$query_vars[] = 'paylabs-mod';
	return $query_vars;
});

//Register payment page
add_filter('the_content', function ($content) {
	$module =  get_query_var('paylabs-mod');
	$item = paylabs_get_page_template($module);
	if ($item['page_name'] !== '' && $item['object'] !== null) {
		$object = $item['object'];
		return $object->get_page_content($page = plugin_dir_path(__FILE__) . 'page/' . $item['page_name'] . '.php');
	}
	return $content;
});

//Load what payment
add_filter('template_include', function ($template) {
	$module = (get_query_var('paylabs-mod'));
	$item = paylabs_get_page_template($module);
	if ($item['template_name'] !== '') {
		return plugin_dir_path(__FILE__) . 'templates/' . $item['template_name'] . '.php';
	}

	return $template;
});

function paylabs_temporary_no_payment_from_wordpress()
{
	return false;
}

add_action('woocommerce_email_header', 'paylabs_email_header', 10, 1);
function paylabs_email_header($email)
{

	//remove need_payment, so the default email linking will be gone;
	add_filter('woocommerce_order_needs_payment', 'paylabs_temporary_no_payment_from_wordpress', 1);
}

add_action('woocommerce_email_footer', 'paylabs_email_footer', 10, 1);
function paylabs_email_footer($email)
{
	global $woocommerce, $post;
	if (!isset($post->ID)) {
		return;
	}
	$order = new WC_Order($post->ID);

	//revert back wordpress need payment
	remove_filter('woocommerce_order_needs_payment', 'paylabs_temporary_no_payment_from_wordpress', 1);



	//to escape # from order id 
	//$order_id = trim(str_replace('#', '', $order->get_order_number()));
	//var_dump($order, $order_id);die();
	if ($order->has_status('pending')) :
		$h5_link = get_post_meta($post->ID, 'h5_link', true);
		printf(__('To pay this order you can click following link: %s', 'woocommerce'), $h5_link);
	endif;
}

add_action('woocommerce_order_details_after_order_table_items', 'add_field_order_details');
function add_field_order_details($order)
{

	$order_id = $order->get_id();
	$order_status  = $order->get_status();
	$va_number = get_post_meta($order_id, 'va_number', true);
	$h5_link = get_post_meta($order_id, 'h5_link', true);
	$qr_code = get_post_meta($order_id, 'qr_code', true);
	$ewallet_link = get_post_meta($order_id, 'ewallet_link', true);
	$judul = "";
	$data_payment = "";

	if (!empty($va_number)) {
		$judul = "Virtual Account";
		$data_payment = esc_html($va_number);
	} elseif (!empty($h5_link)) {
		$judul = "Payment Link";
		$data_payment = '<a style="color: white;background-color: blue;padding: 10px;border-radius: 4px;" target="_NEW" href="' . $h5_link . '">Pay Now</a>';
	} elseif (!empty($qr_code)) {
		$judul = "QR CODE";
		$fileQr = get_post_meta($order_id, 'qr_code_image', true);
		if ($fileQr) {
			$data_payment = '<img style="display:inline-block; width: 350px;" src="' . $fileQr . '" />';
		} else {
			//seharusnya url generate qr
			error_log("File QR Not Found");
		}
	} elseif (!empty($ewallet_link)) {
		$judul = "Payment Link";
		$data_payment = '<a style="color: white;background-color: blue;padding: 10px;border-radius: 4px;" target="_NEW" href="' . $ewallet_link . '">Pay Now</a>';
	}

?>
	<tr>
		<th scope="row"><?= $judul; ?>: </th>
		<td><b><?= (($order_status == "cancelled") ? "Expired" : $data_payment); ?></b></td>
	</tr>
	<?php
	if (!empty($fileQr)) { ?>
		<tr>
			<th scope="row"></th>
			<td><b><a style="color: red;" href="<?= $fileQr; ?>" download>Download</a></b></td>
		</tr>
<?php
	}
}


function paylabs_send_invoice($order_id)
{
	$order = new WC_Order($order_id);

	if (!$order->has_status('pending')) return;

	$billing_address = $order->get_formatted_billing_address(); // for printing or displaying on web page
	$shipping_address = $order->get_formatted_shipping_address();
	$email = $order->billing_email;
	$name = $order->billing_first_name . ' ' . $order->billing_last_name;
	$billing_phone = $order->billing_phone;
	$date = date('M d, Y');
	$h5_link = get_post_meta($order_id, 'h5_link', true);

	$data   = '';
	$data   .= "<table border='0' cellpadding='0' cellspacing='0' width='600'><tbody><tr>
	<td valign='top' style='background-color:#fdfdfd'>
	<table border='0' cellpadding='20' cellspacing='0' width='100%'>
	<tbody>
	<tr>
	<td valign='top' style='padding:48px'>
	<div style='color:#737373;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:14px;line-height:150%;text-align:left'>
	<span>
	<p style='margin:0 0 16px'>
	Hi $name, your order has been received. <br><br>
	To pay this order you can click following link: $h5_link<br><br>
	
	The order is as follows:
	</p>
	</span>
	<h2 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>
	Order # $order_id ( $date )
	</h2>
	<div>
	<div>";
	if (sizeof($order->get_items()) > 0) {
		$data   .=    "<table cellspacing='0' cellpadding='6' style='width:100%;border:1px solid #eee' border='1'>
		<thead>
		<tr>
		<th scope='col' style='text-align:left;border:1px solid #eee;padding:12px'>
		Product
		</th>
		<th scope='col' style='text-align:left;border:1px solid #eee;padding:12px'>
		Quantity
		</th>
		<th scope='col' style='text-align:left;border:1px solid #eee;padding:12px'>
		Price
		</th>
		</tr>
		</thead>
		<tbody>";
		$data   .= $order->email_order_items_table(false, true);
		$data   .=  "</tbody><tfoot>";
		if ($totals = $order->get_order_item_totals()) {
			$i = 0;
			foreach ($totals as $total) {
				$i++;
				$label =    $total['label'];
				$value = $total['value'];
				$data .= "<tr>
			<th scope='row' colspan='2' style='text-align:left; border: 1px solid #eee;'>$label</th>
			<td style='text-align:left; border: 1px solid #eee;'>$value</td>
			</tr>";
			}
		}
		$data .= "</tfoot></table>";
	}

	$data .=
		"<span>
	<h2 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:18px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>
	Customer details
	</h2>
	<p style='margin:0 0 16px'>
	<strong>Email:</strong>
	<a href='mailto:' target='_blank'>
	$email
	</a>
	</p>
	<p style='margin:0 0 16px'>
	<strong>Tel:</strong>
	$billing_phone
	</p>
	<table cellspacing='0' cellpadding='0' style='width:100%;vertical-align:top' border='0'>
	<tbody>
	<tr>
	<td valign='top' width='50%' style='padding:12px'>
	<h3 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:16px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>Billing address</h3>
	<p style='margin:0 0 16px'> $billing_address </p>
	</td>
	<td valign='top' width='50%' style='padding:12px'>
	<h3 style='color:#557da1;display:block;font-family:&quot;Helvetica Neue&quot;,Helvetica,Roboto,Arial,sans-serif;font-size:16px;font-weight:bold;line-height:130%;margin:16px 0 8px;text-align:left'>Shipping address</h3>
	<p style='margin:0 0 16px'> $shipping_address </p>
	</td>
	</tr>
	</tbody>
	</table>
	</span>
	</div>
	</td>
	</tr>
	</tbody>
	</table>
	</td>
	</tr>
	</tbody>
	</table>";

	add_filter('wp_mail_content_type', function () {
		return 'text/html';
	});
	wp_mail($email, 'Your Order Has Been Received', $data);
}

function paylabs_get_order_string($order_id)
{
	global $post;
	$order = new WC_Order($order_id);

	$goods_name_arr = [];
	$product_items = $order->get_items();
	foreach ($product_items as $item) {
		$goods_name_arr[] = $item['name'] . "(" . $item['qty'] . ")";
	}
	$goods_name = implode(',', $goods_name_arr);
	$goods_name = 'Order #' . $order_id . ' - ' . $goods_name;

	if (strlen($goods_name) > 100) {
		$goods_name = substr($goods_name, 0, 97) . '...';
	}

	return $goods_name;
}

add_action('woocommerce_blocks_loaded', 'paylabs_block_support');

function paylabs_block_support()
{
	if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
		require_once dirname(__FILE__) . '/blocks/class-wc-paylabs-blocks.php';
		// priority is important here because this ensures this integration is
		// registered before the WooCommerce Blocks built-in Stripe registration.
		// Blocks code has a check in place to only register if 'stripe' is not
		// already registered.
		$idGateways = [
			'paylabs_h5',
			'paylabs_maybank_va',
			'paylabs_danamon_va',
			'paylabs_qris',
			// Add additional gateways here
		];

		foreach ($idGateways as $val) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) use ($val) {
					$payment_method_registry->register(new WC_Gateway_Paylabs_Blocks_Support($val));
				}
			);
		}
	}
}



function paylabs_reduce_stock_on_order($order_id)
{
	// Get the order object
	$order = wc_get_order($order_id);

	// Check if the order exists
	if (!$order) {
		return;
	}

	// Check if stock has already been reduced for this order
	if (get_post_meta($order_id, '_stock_reduced', true)) {
		return;
	}

	// Reduce stock levels for each item in the order
	foreach ($order->get_items() as $item_id => $item) {
		$product = $item->get_product();
		if ($product && $product->managing_stock()) {
			wc_reduce_stock_levels($order_id);
		}
	}

	// Mark stock as reduced
	update_post_meta($order_id, '_stock_reduced', 'yes');

	// Add any additional custom logic here
	// For example, logging or sending a notification
	error_log('Stock levels reduced for order: ' . $order_id);
}
add_action('woocommerce_thankyou', 'paylabs_reduce_stock_on_order');


function paylabs_enqueue_payment_scripts()
{
	wp_enqueue_script('wc-paylabs-payments-blocks', plugin_dir_url(__FILE__) . 'assets/js/frontend/blocks.js', array('wp-element', 'wp-i18n', 'wc-blocks-registry', 'wp-html-entities'), '1.0.0', true);

	$paylabs_settings = array();
	$availableGateways = WC()->payment_gateways()->get_available_payment_gateways();
	foreach ($availableGateways as $key => $gateway) {
		if (strpos($key, 'paylabs_') === false) {
			unset($availableGateways[$key]);
			continue;
		}
		$paylabs_settings[$key] = [
			'title'       => $gateway->title,
			'description' => $gateway->description,
			'icon'        => $gateway->icon
		];
	}

	wp_localize_script('wc-paylabs-payments-blocks', 'paylabsPaymentMethods', $paylabs_settings);
}
add_action('wp_enqueue_scripts', 'paylabs_enqueue_payment_scripts');
