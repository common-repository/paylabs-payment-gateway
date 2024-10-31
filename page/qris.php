<div id="primary">
    <div id="content" role="main">
        <div class="row">
            <div class="large-12 colums" style="border: double; border-color:#71235a; padding-left:20px; padding-right:20px; padding-top:15px; margin-bottom:20px">
                <h4>Order Details #<?php echo $order->get_id() ?></h4>
                <img width="100" src="<?= $picture ?>">

                <h4><?php echo $order->get_payment_method_title() ?></h4>

                <div class="qris" style="text-align:center">

                    <?php if ($nmid) : ?>
                        <h4>NMID: <?= $nmid ?></h4>
                    <?php endif ?>

                    <?php if ($result['status'] == self::STATUS_SUCCESS) : ?>
                        <h3>Transaksi ini sudah <b style="color:#0a0">LUNAS</b></h3>
                    <?php else : ?>
                        <?php
                        $fileQr = get_post_meta($order->get_id(), 'qr_code_image', true);
                        ?>
                        <img style="display:inline-block; width: 350px;" src="<?= $fileQr; ?>" />
                    <?php endif ?>

                </div>

                <div class="guide">
                    Silahkan scan QRIS di atas ini dengan aplikasi mobile (Gojek, Grab, Shopee, Ovo) menyelesaikan transaksi.
                </div>

                <table>
                    <thead>
                        <th class="product-name" style="text-align: left;"><?php _e('Product', 'woothemes'); ?></th>
                        <th class="product-name" style="text-align: left;"><?php _e('Quantity', 'woothemes'); ?></th>
                        <th class="product-name" style="text-align: left;"><?php _e('Price', 'woothemes'); ?></th>
                        <th class="product-total" style="text-align: left;"><?php _e('Total', 'woothemes'); ?></th>
                    </thead>
                    <tbody>
                        <?php foreach ($order->get_items() as $item_id => $item) : ?>
                            <td><?= $item->get_name() ?></td>
                            <td><?= $item->get_quantity() ?></td>
                            <td><?php echo wc_price($order->get_subtotal(), array('currency' => $order->get_currency())); ?></td>
                            <td><?php echo wc_price($item->get_total(), array('currency' => $order->get_currency())); ?></td>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <th colspan="4">Total <?php echo wc_price($order->get_total(), array('currency' => $order->get_currency())); ?></th>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>