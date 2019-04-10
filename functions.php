<?php
require_once 'vendor/autoload.php';
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
add_filter('woocommerce_account_menu_items', function ($items) {
    $logout = $items['customer-logout'];
    unset($items['customer-logout']);
    $items['stock-management'] = 'Gestion de stock';
    // Insert back the logout item.
    $items['customer-logout'] = $logout;

    // Ne pas afficher l'onglet commande et addresse de livraison, commande pour les entreprises
    $User = wp_get_current_user();
    if (in_array('supplier', $User->roles)) {
        unset($items['orders']);
        unset($items['edit-address']);
    } else {
        unset($items['stock-management']);
    }
    return $items;
}, 999);


add_action('init', function () {
	add_rewrite_endpoint( 'stock-management', EP_ROOT | EP_PAGES );
    add_filter('query_vars', function ($vars) {
        $vars[] = 'stock-management';
        return $vars;
    }, 0);
});

// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
add_action('woocommerce_account_stock-management_endpoint', function () {
    // Access security
    $User = wp_get_current_user();
    if ( ! in_array('supplier', $User->roles)) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        return false;
    }
	echo "Stock management endpoint works!";
});

/*****************************************************
 * Mettre à jour la formulaire d'inscription
 *
 * Edit: form.login.php file for woocommerce template
 *****************************************************/

add_action('user_register', function ($user_id) {
    $User = new WP_User(intval($user_id));
    $User->set_role('particular');

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
//    $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : '';
    update_user_meta($user_id, 'address', sanitize_text_field($address));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));

//    if (isset($role) && $role === 'supplier') {
//        update_user_meta($user_id, 'company_name', sanitize_text_field($company_name));
//    }
});