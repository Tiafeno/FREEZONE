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

add_action('user_register', function ($user_id) {
    $User = new WP_User(intval($user_id));
	if ( isset($_POST['role']) && ! empty( $_POST['role']) ) {
		$role = sanitize_text_field($_POST['role']);
        $User->set_role($role);
	}

	if ( ! empty($_POST['firstname']) && ! empty($_POST['lastname'])) {
	    $firstname = sanitize_text_field($_POST['firstname']);
	    $lastname = sanitize_text_field($_POST['lastname']);
	    $result = wp_update_user([
	        'ID' => intval($user_id),
            'first_name' => $firstname,
            'last_name' => $lastname
        ]);
	    if (is_wp_error($result)) {
	        wc_add_notice($result->get_error_message(), 'error');
        }
    }
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : '';
    update_user_meta($user_id, 'address', sanitize_text_field($address));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));

    if (isset($role) && $role === 'supplier') {
        update_user_meta($user_id, 'company_name', sanitize_text_field($company_name));
    }
});