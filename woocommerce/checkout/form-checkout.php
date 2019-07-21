<?php
/**
 * Checkout Form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- header -->
<div class="apus-checkout-header">
	<div class="apus-checkout-step">
		<ul class="clearfix">
			<li >
				<div class="inner">
				<?php printf(__( '<span class="step">%s</span>', 'yozi' ), '01' ); ?>
				<span class="inner-step">
					<?php echo esc_html__('Shopping Cart','yozi'); ?>
				</span>
				</div>
			</li>
			<li class="active">
				<div class="inner">
				<?php printf(__( '<span class="step">%s</span>', 'yozi' ), '02' ); ?>
				<span class="inner-step">
					<?php echo esc_html__('Checkout','yozi'); ?>
				</span>
				</div>
			</li>
			<li>
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
wc_print_notices();

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout
if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
	echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', esc_html__( 'You must be logged in to checkout.', 'yozi' ) );
	return;
}

$User = wp_get_current_user();
$restricted_msg = "<div style='margin-bottom: 40px'>Vous devez avoir un compte entreprise pour pouvoir continuer cette opération</div>";
if (in_array('fz-particular', $User->roles) || in_array('fz-company', $User->roles)) {
} else {
    echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', $restricted_msg );
	
    if ( ! $checkout->enable_signup && ! $checkout->enable_guest_checkout && ! is_user_logged_in() ) {
		echo apply_filters( 'woocommerce_checkout_must_be_logged_in_message', esc_html__( 'You must be logged in to checkout.', 'yozi' ) );
	return;
}
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

<div class="row">
    <div class="col-md-12 col-xs-12">
        <div class="details-review">
            <div class="order-review">
                <h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'yozi' ); ?></h3>
                <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

                <div id="order_review" class="woocommerce-checkout-review-order">
                    <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                </div>

                <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
            </div>
        </div>
    </div>
	<div class="col-md-12 col-xs-12">
		<div class="details-check">
		<?php if ( $checkout->get_checkout_fields() ) : ?>
			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div class="col2-set" id="customer_details">
				<div class="col-1">
					<?php //do_action( 'woocommerce_checkout_billing' ); ?>
				</div>

				<div class="col-2">
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
		<?php endif; ?>
		</div>
	</div>
</div>

	

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
