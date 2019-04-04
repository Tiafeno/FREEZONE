<?php
	
add_action( 'wp_enqueue_scripts', function () {
    $theme = wp_get_theme('freezone');
    wp_enqueue_style( 'yozi-child-theme', get_stylesheet_directory_uri() . '/style.css', [], $theme->get('Version') );
}, 1000 );

// Ces filtres permet de ne pas afficher les prix des produits Woocommerce
add_filter( 'woocommerce_variable_sale_price_html', 'remove_prices', 10, 2 );
add_filter( 'woocommerce_variable_price_html', 'remove_prices', 10, 2 );
add_filter( 'woocommerce_get_price_html', 'remove_prices', 10, 2 );
function remove_prices( $price, $product ) {
    if ( ! is_admin() ) $price = '';
    return $price;
}
