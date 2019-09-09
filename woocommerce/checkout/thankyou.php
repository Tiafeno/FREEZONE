<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style type="text/css">
	.woocommerce section.woocommerce-order-details {
		display: none;
	}
</style>
<!-- header -->
<div class="apus-checkout-header">

	<div class="apus-checkout-step">
		<ul class="clearfix">
			<li>
				<div class="inner">
				<?php printf(__( '<span class="step">%s</span>', 'yozi' ), '01' ); ?>
				<span class="inner-step">
					<?php echo esc_html__('Shopping Cart','yozi'); ?>
				</span>
				</div>
			</li>
			<li>
				<div class="inner">
				<?php printf(__( '<span class="step">%s</span>', 'yozi' ), '02' ); ?>
				<span class="inner-step">
					<?php echo esc_html__('Checkout','yozi'); ?>
				</span>
				</div>
			</li>
			<li class="active">
				<div class="inner">
				<?php printf(__( '<span class="step">%s</span>', 'yozi' ), '03' ); ?>
				<span class="inner-step">
					<?php echo esc_html__('Order Completed','yozi'); ?>
				</span>
				</div>
			</li>
		</ul>
	</div>
</div>
<?php

if ( $order ) : ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<p class="woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'yozi' ); ?></p>

		<p class="woocommerce-thankyou-order-failed-actions">
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'yozi' ) ?></a>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My Account', 'yozi' ); ?></a>
			<?php endif; ?>
		</p>

	<?php else : ?>

		<p class="woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'yozi' ), $order ); ?></p>

		<ul class="woocommerce-thankyou-order-details order_details">
			<li class="order">
				<?php esc_html_e( 'Order Number:', 'yozi' ); ?>
				<strong><?php echo trim($order->get_order_number()); ?></strong>
			</li>
			<li class="date">
				<?php esc_html_e( 'Date:', 'yozi' ); ?>
				<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
			</li>
			<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
				<li class="woocommerce-order-overview__email email">
					<?php esc_html_e( 'Email:', 'yozi' ); ?>
					<strong><?php echo trim($order->get_billing_email()); ?></strong>
				</li>
			<?php endif; ?>
		</ul>
		<div class="clear"></div>

	<?php endif; ?>
	<div class="woo-pay-perfect text-theme">
		Votre demande a bien été envoyée et sera étudiée ultérieurement  
	</div>
	<?= do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
	<?php
	$shop_url = wc_get_page_permalink('shop');
	?>
	<p class="buttons clearfix" style="margin-bottom: 40px">
		<a href="<?= $shop_url ?>" class="btn btn-block btn-primary wc-forward" style="width:inherit">Poursuivre la demande</a>
	</p>
<?php else : ?>

	<p class="woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'yozi' ), null ); ?></p>

<?php endif; ?>