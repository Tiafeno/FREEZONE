<?php

require_once 'inc/fz-functions.php';
	
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


/********************************************************************
 * Ajouter une nouvelle tab dans la page "mon compte" de woocommerce
 ********************************************************************/
add_action('init', function () {
	add_rewrite_endpoint( 'premium-support', EP_ROOT | EP_PAGES );
});

add_filter('query_vars', function ($vars) {
	$vars[] = 'premium-support';
    return $vars;
}, 0);

add_filter('woocommerce_account_menu_items', function ($items) {
	$items['premium-support'] = 'Premium Support';
	//unset($items['orders']);
    return $items;
});

// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
add_action('woocommerce_account_premium-support_endpoint', function () {
	echo "Finel";
});

/*****************************************************
 * Mettre à jour la formulaire d'inscription
 *
 * Edit: form.login.php file for woocommerce template
 *****************************************************/

add_action('register_form', function () {
	$role = ( ! empty( $_POST['role'] ) ) ? sanitize_text_field( $_POST['role'] ) : '';
	$role = esc_attr($role);
	$content = <<<EOF
	<p>
		<label for="first_name">Type de compte<br />
			<input type="text" name="role" id="role" class="input" value="{$role}" size="25" />
		</label>
	</p>
EOF;
	return $content;
});

add_action('user_register', function ($user_id) {
	if ( ! empty( $_POST['role']) ) {
		update_user_meta($user_id, '__role', sanitize_text_field($_POST['role']));
	}
});