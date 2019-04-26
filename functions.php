<?php
require_once 'vendor/autoload.php';
require_once 'inc/fz-functions.php';

add_action('wp_enqueue_scripts', function () {
    $theme = wp_get_theme('freezone');
    wp_enqueue_style('yozi-child-theme', get_stylesheet_directory_uri() . '/style.css', [], $theme->get('Version'));
}, 1000);

// Ces filtres permet de ne pas afficher les prix des produits Woocommerce
add_filter('woocommerce_variable_sale_price_html', 'remove_prices', 10, 2);
add_filter('woocommerce_variable_price_html', 'remove_prices', 10, 2);
add_filter('woocommerce_get_price_html', 'remove_prices', 10, 2);
function remove_prices ($price, $product)
{
    if (!is_admin()) $price = '';
    return $price;
}


/********************************************************************
 * Ajouter une nouvelle tab dans la page "mon compte" de woocommerce
 ********************************************************************/
add_filter('woocommerce_account_menu_items', function ($items) {
    $logout = $items['customer-logout'];
    unset($items['customer-logout']);
    $items['stock-management'] = 'Gestion de stock';
    $items['stock-management'] = 'Gestion de stock';
    // Insert back the logout item.
    $items['demandes'] = "Demandes";
    $items['customer-logout'] = $logout;


    // Rennomer la commande pour une demande

    // Ne pas afficher l'onglet commande et addresse de livraison, commande pour les entreprises
    $User = wp_get_current_user();
    if (in_array('fz-supplier', $User->roles)) {
        unset($items['orders']);
        unset($items['edit-address']);
    } else {
        unset($items['stock-management']);
    }
    return $items;
}, 999);

add_action('init', function () {
    add_rewrite_endpoint('sav', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('stock-management', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('demandes', EP_ROOT | EP_PAGES);
    add_filter('query_vars', function ($vars) {
        $vars[] = 'stock-management';
        $vars[] = 'demandes';
        return $vars;
    }, 0);


});

// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
add_action('woocommerce_account_stock-management_endpoint', function () {
    // Access security
    $User = wp_get_current_user();
    if (!in_array('fz-supplier', $User->roles)) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        return false;
    }
    echo "Stock management endpoint works!";
});

add_action('woocommerce_account_demandes_endpoint', function () {
    global $Engine;
    $fzModel = new \model\fzModel();
    $User = wp_get_current_user();
    if (!in_array('fz-particular', $User->roles)) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        return false;
    }

    if (!$fzModel->has_user_quotation($User->ID)) {
        echo 'Aucune demande';
        return;
    }

    $user_quotations = $fzModel->get_user_quotations($User->ID);
    $quotations = [];
    foreach ( $user_quotations as $quotation ) {
        $order = new WC_Order(intval($quotation->order_id));
        $items = $order->get_items(); // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item.html (WC_Order_Item_Product)
        $products = [];
        foreach ($items as $item) {

            //$item->set_quantity(3);
            //$item->save();

            $product = new stdClass();
            $product->quantity = $item->get_quantity();
            $product->product = wc_get_product($item['product_id']);
            $products[] = $product;
        }
        $quotations[] = [
            'items' => $items,
            //'products' => $products,
            'status' => $quotation->status,
            'date_add' => $quotation->date_add
        ];
    }

    //print_r($quotations);
    echo $Engine->render("@WC/demande/quotations-table.html", []);
}, 10);

add_action('template_redirect', function () {
    global $wp_query;
    // if this is not a request for sav or a singular object then bail
    if (!isset($wp_query->query_vars['sav']))
        return;

});

/*****************************************************
 * Mettre à jour la formulaire d'inscription
 *
 * Edit: form.login.php file for woocommerce template
 *****************************************************/

add_action('user_register', function ($user_id) {
    $User = new WP_User(intval($user_id));
    // Ajouter les utilisateurs inscrits en tant que particulier
    $User->set_role('fz-particular');

    if (!empty($_POST['firstname']) && !empty($_POST['lastname'])) {
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
    update_user_meta($user_id, 'address', sanitize_text_field($address));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));

});


add_action('woocommerce_thankyou', 'fz_order_received', 10, 1);
function fz_order_received ($order_id)
{
    $fzModel = new \model\fzModel();
    if (!$fzModel->quotation_exist($order_id))
        $fzModel->set_quotation($order_id, 0);
}