<?php
require_once get_stylesheet_directory() . '/vendor/autoload.php';
require_once get_stylesheet_directory() . '/inc/fz-functions.php';

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
    unset($items['orders']);

    $items['stock-management'] = 'Gestion de stock';
    $items['stock-management'] = 'Gestion de stock';
    // Insert back the logout item.
    $items['demandes'] = "Demandes";
    $items['customer-logout'] = $logout;

    // Ne pas afficher l'onglet commande et addresse de livraison, commande pour les entreprises
    $User = wp_get_current_user();
    if (in_array('fz-supplier', $User->roles)) {
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

    $my_account = get_post(wc_get_page_id('myaccount'));
    $my_account_sanitize_title = sanitize_title($my_account->post_title);
    add_rewrite_tag('%componnent%', '([^&]+)');
    add_rewrite_tag('%id%', '([^&]+)');
    add_rewrite_rule("^demandes/quotation/([^/]*)/?", 'index.php?componnent=edit&id=$matches[1]', 'top');


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
    global $Engine, $wp_query;

    $fzModel = new \model\fzModel();
    $User = wp_get_current_user();
    if (!in_array('fz-particular', $User->roles)) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        return false;
    }

    if (!$fzModel->has_user_quotation($User->ID)) {
        echo 'Aucune demande';
        return false;
    }

    $user_quotations = $fzModel->get_user_quotations($User->ID);

    if (isset($wp_query->query_vars['componnent'])) {
        $componnent = sanitize_text_field($wp_query->query_vars['componnent']);
        $order_id = $wp_query->query_vars['id'];
        switch ($componnent):
            case 'edit':
                $order = new \classes\fzQuotation(intval($order_id));
                $items = $order->get_items(); // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item.html (WC_Order_Item_Product)
                $products = [];
                foreach ( $items as $item ) {

                    $quotation_product = new \classes\fzQuotationProduct((int)$item['product_id'], (int)$order_id);
                    $products[] = $quotation_product;
                }

                $quotation = [
                    'order_id' => (int)$order_id,
                    'products' => $products,
                    'status'   => intval($order->status),
                    'date_add' => $order->date_add
                ];

                wc_add_notice("Module under development. Thank you for coming back sooner @Falicrea", 'error');
                wc_print_notices();
                echo $Engine->render('@WC/demande/quotation-edit.html', ['quotation' => $quotation]);
                break;

            case 'update':

//                $item->set_quantity(4);
//                $item->save();
                echo "Update demande works";
                break;

            case 'confirmation':

                break;
        endswitch;
    } else {
        $quotations = [];
        foreach ( $user_quotations as $quotation ) {

            $quotations[] = [
                'order_id' => $quotation->order_id,
                'status'   => intval($quotation->status),
                'date_add' => $quotation->date_add
            ];

        }

        wc_print_notices();
        echo $Engine->render("@WC/demande/quotations-table.html", ['quotations' => $quotations]);
    }


}, 10);

add_action('template_redirect', function () {
    global $wp_query;
    // if this is not a request for sav or a singular object then bail
    if (isset($wp_query->query_vars['sav'])) {

    }

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
    if (!$fzModel->quotation_exist($order_id)):
        $fzModel->set_quotation($order_id, 0); // For current user

        $order = new WC_Order(intval($order_id));
        $items = $order->get_items(); // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item.html (WC_Order_Item_Product)
        foreach ( $items as $item ) {
            $fzModel->set_product_qt($order_id, (int)$item['product_id']);
        }

    endif;
}

// Effacer les demandes et les articles de la demande dans la base de donnée
add_action('woocommerce_delete_order', 'fz_delete_order', 10, 1);
//add_action('wp_trash_post', 'fz_delete_order', 10, 1);
add_action('before_delete_post', 'fz_delete_order', 10, 1);
function fz_delete_order ($post_id)
{
    $type = get_post_type($post_id);
    if($type == 'shop_order'){
        $fzModel = new \model\fzModel();
        $fzModel->remove_quotation($post_id);
        $fzModel->remove_quotation_pts($post_id);
    }
}