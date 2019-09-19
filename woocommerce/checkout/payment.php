<?php
/**
 * Checkout Payment Section
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer).
 * will need to copy the new files to your theme to maintain compatibility. We try to do this.
 * as little as possible, but it does happen. When this occurs the version of the template file will.
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.4.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<?php if ( WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods">
			<?php
				if ( ! empty( $available_gateways ) ) {
					foreach ( $available_gateways as $gateway ) {
						wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
					}
				} else {
					echo '<li>' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'yozi' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'yozi' ) ) . '</li>';
				}
			?>
		</ul>
	<?php endif; ?>
		<ul style="margin-top: 15px; font-weight: bold">
				<li>Pour tout achat de moins de 100.000 HT prévoir des frais de transport de l’ordre de 12.600 HT </li>
				<li>Pour les clients basés en province le transport et à leur charge, nous pouvons vous livrer les marchandises auprès de votre transporteur et cela gratuitement</li>
		</ul>

	<div class="form-row place-order">
		<noscript>
			<?php esc_html_e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'yozi' ); ?>
			<br/><input type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'yozi' ); ?>" />
		</noscript>

		<p style="color: red; font-size: 14px">
			Nous tenons à vous rappeler l’importance de mettre les bonnes quantités dans votre devis car toute modification de quantité 
			fera l’objet d’une nouvelle demande, ce qui peut occasionner un changement de prix. <br>Nous vous en remercions par avance 
		</p>
		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<input type="submit" class="btn btn-theme btn-block alt" name="woocommerce_checkout_place_order" id="place_order" value="Soumettre ma demande" data-value="' . esc_attr( $order_button_text ) . '" />' ); ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! is_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
