<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;
?>

<?php do_action( 'woocommerce_before_mini_cart' ); ?>
<div class="shopping_cart_content">
	<div class="cart_list <?php echo esc_attr( $args['list_class'] ); ?>">

		<?php if ( sizeof( WC()->cart->get_cart() ) > 0 ) : ?>

			<?php
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
					$product_id   = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

					if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {

						$product_name  = apply_filters( 'woocommerce_cart_item_name', $_product->get_title(), $cart_item, $cart_item_key );
						$thumbnail     = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
						$product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
						
						?>
						<div class="media widget-product">
							<div class="media-left media-middle">
								<a href="<?php echo get_permalink( $product_id ); ?>" class="image">
									<?php echo trim($thumbnail); ?>
								</a>
							</div>
							<div class="cart-main-content media-body media-middle">
								<?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>

								<?php echo apply_filters( 'woocommerce_widget_cart_item_quantity', '<span class="quantity text-theme">' . sprintf( '%s &times; %s', $cart_item['quantity'], $product_price ) . '</span>', $cart_item, $cart_item_key ); ?>
								<h3 class="name">
									<?php if ( ! $_product->is_visible() ) : ?>
										<?php echo str_replace( array( 'http:', 'https:' ), '', $thumbnail ) . $product_name . '&nbsp;'; ?>
									<?php else : ?>
										<a href="<?php echo esc_url( $product_permalink ); ?>">
											<?php echo trim($product_name); ?>
										</a>
									<?php endif; ?>
								</h3>
								<?php
								    echo apply_filters(
								        'woocommerce_cart_item_remove_link',
								        sprintf(
								            '<a href="%s" class="remove" title="%s"> <i class="ion-android-close"></i> </a>',
								            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								            esc_html__( 'Remove this item', 'yozi' )
								        ),
								        $cart_item_key
								    );
								?>


							</div>
						</div>
						<?php
					}
				}
			?>
			

		<?php else : ?>

			<p class="total text-theme empty"><strong><?php esc_html_e( 'Currently Empty', 'yozi' ); ?>:</strong> <?php echo WC()->cart->get_cart_subtotal(); ?></p>
			<p class="buttons clearfix">
				<a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="btn btn-block btn-primary wc-forward"><?php esc_html_e( 'Continue shopping', 'yozi' ); ?></a>
			</p>
		<?php endif; ?>
	</div><!-- end product list -->

	<?php if ( sizeof( WC()->cart->get_cart() ) > 0 ) : ?>

		<p class="total text-theme"><strong><?php esc_html_e( 'Total', 'yozi' ); ?>:</strong> <?php echo WC()->cart->get_cart_subtotal(); ?></p>

		<?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

		<p class="buttons clearfix">
			<a href="<?php echo wc_get_cart_url(); ?>" class="btn btn-primary wc-forward"><?php esc_html_e( 'View Cart', 'yozi' ); ?></a>
			<a href="<?php echo wc_get_checkout_url(); ?>" class="btn btn-theme checkout wc-forward"><?php esc_html_e( 'Checkout', 'yozi' ); ?></a>
		</p>

	<?php endif; ?>
</div>
<?php do_action( 'woocommerce_after_mini_cart' ); ?>