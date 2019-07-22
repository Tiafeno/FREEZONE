<?php
require_once get_stylesheet_directory() . '/vendor/autoload.php';
require_once get_stylesheet_directory() . '/inc/fz-mail.php';
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

add_action('init', function () {
    add_rewrite_endpoint('sav', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('faq', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('stock-management', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('demandes', EP_ROOT | EP_PAGES);
    add_filter('query_vars', function ($vars) {
        $vars[] = 'stock-management';
        $vars[] = 'demandes';
        $vars[] = 'faq';
        return $vars;
    }, 0);

    add_rewrite_tag('%componnent%', '([^&]+)');
    add_rewrite_tag('%id%', '([^&]+)');
    add_rewrite_tag('%conf%', '([^&]+)');
    add_rewrite_tag('%pa_%', '([^&]+)'); // paged
    flush_rewrite_rules();
});

// effacer tous les articles qui utilise ce produit comme reference
add_action('init', function () {
    add_action('delete_post', function ($post_id) {
        global $wpdb;
        $post_type = get_post_type($post_id);
        if ($post_type === 'product'):
            $get_articles_sql = <<<TAG
SELECT ID FROM {$wpdb->posts} WHERE post_type = 'fz_product' 
  AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE CONVERT(LOWER(`meta_key`) USING utf8mb4) = 'product_id' 
    AND meta_value = $post_id)
TAG;
            $results = $wpdb->get_results($get_articles_sql);
            foreach ($results as $post) {
                wp_delete_post(intval($post->ID), true);
            }

            endif;
    }, 10, 1);


}, 10);

add_action('template_redirect', function () {
    global $wp_query;
    // if this is not a request for sav or a singular object then bail
    if (isset($wp_query->query_vars['sav'])) {

    }

});

add_filter('woocommerce_account_menu_items', function ($items) {
    $logout = $items['customer-logout'];

    unset($items['customer-logout']);
    unset($items['orders']);

    $items['stock-management'] = 'Gestion de stock';
    $items['demandes'] = "Demandes";
    $items['faq'] = "FAQ";

    // Insert back the logout item.
    $items['customer-logout'] = $logout;

    $User = wp_get_current_user();
    if (in_array('fz-supplier', $User->roles)) {
        unset($items['edit-address'], $items['demandes']);
    } else {
        unset($items['stock-management']);
    }
    return $items;
}, 999);

// Filtre pour le formulaire de commande ou demande
add_filter('woocommerce_checkout_fields', function ($fields) {

    $fields['billing']['billing_country']['default'] = 'MG';
    $fields['billing']['billing_country']['required'] = false;
    $fields['billing']['billing_state']['required'] = false;
    $fields['billing']['billing_first_name']['required'] = false;
    $fields['billing']['billing_last_name']['required'] = false;
    $fields['billing']['billing_company']['required'] = false;
    $fields['billing']['billing_address_1']['required'] = false;
    $fields['billing']['billing_address_2']['required'] = false;
    $fields['billing']['billing_city']['required'] = false;
    $fields['billing']['billing_postcode']['required'] = false;
    $fields['billing']['billing_phone']['required'] = false;
    $fields['billing']['billing_email']['required'] = false;

    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_email']);
    unset($fields['billing']['billing_phone']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_first_name']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);

    $fields['shipping']['shipping_country']['default'] = 'MG';
    $fields['shipping']['shipping_country']['required'] = false;
    $fields['shipping']['shipping_first_name']['required'] = false;
    $fields['shipping']['shipping_last_name']['required'] = false;
    $fields['shipping']['shipping_company']['required'] = false;
    $fields['shipping']['shipping_address_1']['required'] = false;
    $fields['shipping']['shipping_address_2']['required'] = false;
    $fields['shipping']['shipping_city']['required'] = false;
    $fields['shipping']['shipping_postcode']['required'] = false;
    $fields['shipping']['shipping_phone']['required'] = false;
    $fields['shipping']['shipping_email']['required'] = false;
    $fields['shipping']['shipping_state']['required'] = false;

    unset($fields['shipping']['shipping_country']);
    unset($fields['shipping']['shipping_first_name']);
    unset($fields['shipping']['shipping_last_name']);
    unset($fields['shipping']['shipping_company']);
    unset($fields['shipping']['shipping_address_1']);
    unset($fields['shipping']['shipping_address_2']);
    unset($fields['shipping']['shipping_city']);
    unset($fields['shipping']['shipping_postcode']);
    unset($fields['shipping']['shipping_phone']);
    unset($fields['shipping']['shipping_email']);
    unset($fields['shipping']['shipping_state']);


    // Remplir automatiquement les champs pour l'étape de la demande

    return $fields;
}, 9999);

add_filter( 'woocommerce_default_address_fields' , 'disable_address_fields_validation', 999 );
function disable_address_fields_validation( $address_fields_array ) {
	return $address_fields_array;
}

// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
add_action('woocommerce_account_stock-management_endpoint', function () {
    global $wpdb;

    $posts_per_page = 10;
    // Access security
    $User = wp_get_current_user();
    // Vérification d'autorisation utilisateur
    if (!in_array('fz-supplier', $User->roles)) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        wc_print_notices();
        wc_clear_notices();
        return false;
    }
    global $Engine, $fz_model, $wp_query;

    if (isset($wp_query->query_vars['componnent'])) {
        $componnent = sanitize_text_field($wp_query->query_vars['componnent']);
        switch ($componnent):
            case 'edit':
                $article_id = isset($wp_query->query_vars['id']) ? $wp_query->query_vars['id'] : 0;
                if (!$article_id) return false;
                $article_id = intval($article_id);
                $fz_product = new \classes\fzSupplierArticle((int)$article_id);
                $parent_categories = get_terms('product_cat', [
                    'hide_empty' => false, 'parent' => 0
                ]);
                $categories = [];
                foreach ( $parent_categories as $ctg ) {
                    $terms = get_terms('product_cat', ['parent' => $ctg->term_id, 'hide_empty' => false]);
                    foreach ( $terms as $term ) {
                        $categories[] = $term;
                    }
                }

                if ($_POST) {
                    if (isset($_POST['price']) && isset($_POST['stock'])) {
                        $regular_price = sanitize_text_field($_POST['price']);
                        $stock = sanitize_text_field($_POST['stock']);
                        update_field('price', intval($regular_price), $article_id);
                        update_field('total_sales', intval($stock), $article_id);
                        update_field('date_review', date_i18n('Y-m-d H:m:s'), $article_id);

                        wc_add_notice("Article mis à jour avec succès", 'success');
                    }
                }

                wc_print_notices();
                wc_clear_notices();
                echo $Engine->render('@WC/stock/article-edit.html', [
                    'article' => $fz_product,
                    'back_link' => wc_get_account_endpoint_url('stock-management')
                ]);
                break;

            case 'new':
                wp_enqueue_style('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css');
                wp_enqueue_script('select2', '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', ['jquery']);

                wp_enqueue_script('article-new', get_stylesheet_directory_uri() . '/assets/js/article-new.js', ['jquery', 'select2'], '0.0.3');
                wp_localize_script('article-new', 'fzOptions', [
                    'root' => esc_url_raw(rest_url()),
                    'admin_ajax' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wp_rest')
                ]);

                if ($_POST) {
                    $price = isset($_POST['price']) ? sanitize_text_field($_POST['price']) : 0;
                    $stock = isset($_POST['stock']) ? sanitize_text_field($_POST['stock']) : 0;
                    $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : 0;

                    // Vérifier si le client vas ajouter un doublon
                    $verify_product_exist_args  = [
                        'post_type'   => 'fz_product',
                        'post_status' => 'any',
                        'posts_per_page' => 1,
                        'meta_query'  => [
                            [
                                'key'   => 'user_id',
                                'value' => intval($User->ID)
                            ],
                            [
                                'key'   => 'product_id',
                                'value' => intval($product_id)
                            ]
                        ]
                    ];
                    $product_exists = get_posts($verify_product_exist_args);

                    if ( ! $product_exists ) {
                        $product = get_post( (int) $product_id);
                        if ($price && $stock && $product_id) {
                            $result = wp_insert_post([
                                'post_type'   => 'fz_product',
                                'post_status' => 'pending',
                                'post_title'  => $product->post_title
                            ], true);

                            if (is_wp_error($result)) {
                                wc_add_notice($result->get_error_message(), 'error');
                            } else {
                                update_field('price', intval($price), $result);
                                update_field('total_sales', intval($stock), $result);
                                update_field('user_id', intval($User->ID), $result);
                                update_field('product_id', (int) $product_id, $result);
                                update_field('date_review', date_i18n('Y-m-d H:i:s'), $result);
                                update_field('date_add', date_i18n('Y-m-d H:i:s'), $result);
                                wc_add_notice("Article ajouter avec succès", 'success');

                                // Envoyer un mail au administrateur
                                do_action('fz_insert_new_article', $result);

                                wp_redirect(wc_get_account_endpoint_url('stock-management'));
                            }
                        } else {
                            wc_add_notice("Une erreur s'est produite pendant le traitement de donnée", 'error');
                        }
                    } else {
                        wc_add_notice("Cette article existe déja dans votre catalogue", 'notice');
                    }

                }

                wc_print_notices();
                wc_clear_notices();
                echo $Engine->render('@WC/stock/article-new.html', [
                    'products' => $fz_model->get_products(),
                    'back_link' => wc_get_account_endpoint_url('stock-management')
                ]);
                break;

            case 'trash':
                $error = false;
                $stock_management_endpoint_url = wc_get_account_endpoint_url("stock-management");
                $article_id = isset($wp_query->query_vars['id']) ? $wp_query->query_vars['id'] : 0;
                if (!$article_id) return false;

                $article_id = intval($article_id);
                $postArticle = get_post($article_id);
                if (is_null($postArticle)) $error = true;

                if ($error) {
                    wc_add_notice("Une erreur s'est produit pendant l'operation", 'error');
                } else {
                    $confirmation = isset($wp_query->query_vars['conf']) ? $wp_query->query_vars['conf'] : null;
                    $confirmation = is_null($confirmation) ? $confirmation : filter_var($confirmation, FILTER_VALIDATE_BOOLEAN);
                    if (!is_null($confirmation) && $confirmation) {
                        // Supprimer définitivement l'article dans la base de donnée
                        wp_delete_post($article_id, true);

                        wc_add_notice("Article supprimer avec succèss", 'success');
                        wc_print_notices();
                        echo "<a href='{$stock_management_endpoint_url}' class='btn btn-sm btn-default'>Retour</a>";
                    }

                    if (!is_null($confirmation) && !$confirmation) {
                        wp_redirect(remove_query_arg(['componnent', 'id'], $stock_management_endpoint_url));
                        exit();
                    }

                    if (is_null($confirmation)) {
                        $content = "Voulez-vous vraiment supprimer cet article? <br> <b>{$postArticle->post_title}</b>";
                        $content .= "<div>";
                        $content .= "<a href='?componnent=trash&id={$article_id}&conf=true' class='btn btn-sm btn-primary'>OUI</a>";
                        $content .= "<a href='?componnent=trash&id={$article_id}&conf=false' " .
                            " style='margin-left: 10px' class='btn btn-sm btn-default'>NON</a>";
                        $content .= "</div>";
                        echo $content;
                    }


                }

                wc_clear_notices();
                break;
        endswitch;
    } else {
        $paged = get_query_var('pa_') ? get_query_var('pa_') : 1;

        add_filter('posts_join', function ($join) {
            global $wpdb;
            $join .= " JOIN $wpdb->postmeta as pm ON (pm.post_id = {$wpdb->posts}.ID)";

            return $join;
        }, 10, 1);

        add_filter('posts_groupby', function ($groupby) {
            global $wpdb;
            $groupby = "{$wpdb->posts}.ID HAVING COUNT(*) > 0";

            return $groupby;
        }, 10, 1);

        add_filter('posts_where', function ($where) {
            $User = wp_get_current_user();
            $where .= " AND pm.meta_key = 'user_id' AND pm.meta_value = $User->ID";

            return $where;
        }, 10, 1);

        $args = [
            'post_type'      => "fz_product",
            'post_status'    => "publish",
            'posts_per_page' => $posts_per_page,
            'paged' => $paged
        ];
        $query = new WP_Query($args);
        $pagination = '<div class="apus-pagination"><ul class="page-numbers">';
        $pagination .= paginate_links([
            'base'    => @add_query_arg('pa_', '%#%'),
            'format'  => '?pa_=%#%',
            'current' => max(1, get_query_var('pa_')),
            'type'    => 'list',
            'current' => $paged,
            'total'   => $query->max_num_pages

        ]);
        $pagination .= '</ul></div>';
        $articles = [];
        foreach ( $query->posts as $post ) {
            $articles[] = new \classes\fzSupplierArticle($post->ID);
        }

        echo $Engine->render('@WC/stock/article-table.html', [
            'products' => $articles,
            'new_article_link' => wc_get_account_endpoint_url('stock-management') . '?componnent=new'
        ]);
        echo $pagination;
    }
}, 10);

add_action('woocommerce_account_demandes_endpoint', function () {
    global $Engine, $wp_query;

    wp_enqueue_script('underscore');
    $User = wp_get_current_user();
    if (!in_array('fz-particular', $User->roles) && !in_array('fz-company', $User->roles)) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        wc_print_notices();
        wc_clear_notices();
        return false;
    }

    // https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
    $user_quotations = wc_get_orders(['customer_id' => $User->ID]);

    if (empty($user_quotations)) {
        $shop_url = get_permalink(wc_get_page_id('shop'));
        $content = '<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">';
		$content .= '<a class="woocommerce-Button button" href="'.$shop_url.'">';
		$content .=	'Voir les catalogues</a> Aucune demande n’a encore été passée.	</div>';
        echo $content;
        
        return false;
    }

    if (isset($wp_query->query_vars['componnent'])) {
        $componnent = sanitize_text_field($wp_query->query_vars['componnent']);
        $order_id   = $wp_query->query_vars['id'];
        switch ($componnent):
            case 'edit':

                if ($_POST) {
                    $validate = false;
                    $order = new WC_Order(intval($order_id));
                    // @e.g: qt_1520 = 2 => qt_{item_id} = {quantity}
                    foreach ( $_POST as $name => $value ) {
                        if (strpos($name, '_') !== false) {
                            $names = explode('_', $name);
                            if (empty($names) || $names[0] !== 'qt') continue;
                            $current_item_id = intval($names[1]);
                            $quantity = intval($value);
                            $items = $order->get_items();

                            foreach ( $items as $id => $item ) {
                                if ($current_item_id === $id) {

                                    $suppliers = wc_get_order_item_meta( $id, 'suppliers', true );
                                    $suppliers = json_decode(stripslashes($suppliers));

                                    $current_total = (int)$item->get_total();
                                    $current_price = $current_total / $item->get_quantity();

                                    $new_total = $current_price * $quantity;
                                    $item->set_quantity($quantity);
                                    $item->set_total((string)$new_total);

                                    $item->save();

                                    $rest = 0;
                                    $suppliers = array_map(function($supplier) use ($quantity, &$rest) {
                                        $article_id = (int) $supplier->article_id;
                                        $article  = new \classes\fzSupplierArticle($article_id);
                                        $quantity = 0 === $rest ? $quantity : $rest;
                                        if ($article->total_sales < $quantity) {
                                            $take = $article->total_sales;
                                            $rest = $quantity - $article->total_sales;
                                        } else {
                                            $take = $quantity;
                                            $rest = 0;
                                        }

                                        $supplier->get = $take;

                                        return $supplier;
                                    }, $suppliers);

                                    // https://docs.woocommerce.com/wc-apidocs/function-wc_update_order_item_meta.html
                                    wc_update_order_item_meta($current_item_id, 'suppliers', json_encode($suppliers));
                                }
                            }
                            $validate = true;
                        }
                    }

                    if ($validate) {
                        update_field('position', 3, $order->get_id());
                        $order->update_status('completed');
                        // Envoyer un mail au administrateur
                        do_action('complete_order', $order->get_id());
                    }
                    wc_add_notice("Validation envoyer avec succès", 'success');
                    unset($order, $items);
                }

                $order = new \classes\fzQuotation(intval($order_id));
                $items = $order->get_items(); // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item.html (WC_Order_Item_Product)
                $products = [];
                foreach ( $items as $item ) {

                    $quotation_product = new \classes\fzQuotationProduct((int)$item['product_id'], (int)$order_id);
                    $products[] = $quotation_product;
                }

                $meta_data_suppliers = [];
                foreach ( $products as $qProduct ) {

                    if (is_null($qProduct->suppliers) || empty($qProduct->suppliers)) continue;
                    $suppliers = array_map(function ($supplier) {
                        $fzSupplierArticle = new \classes\fzSupplierArticle(intval($supplier->article_id));

                        $supplier->price = intval($fzSupplierArticle->regular_price);
                        $supplier->get   = intval($supplier->get);
                        $supplier->total_sales = $fzSupplierArticle->total_sales;

                        return $supplier;
                    }, $qProduct->suppliers);
                    $meta_data_suppliers[] = $suppliers;
                }

                switch ($order->get_position()) {
                    case 0:
                        wc_add_notice("Votre demande est en cours de validation. Veuillez réessayer plus tard", "notice");
                        break;
                    case 2:
                        wc_add_notice("Votre demande est désactivé par l'administrateur", "error");
                        break;
                    case 3:
                        wc_add_notice("Vous ne pouvez plus modifier cette demande", "notice");
                        break;
                }

                $quotation = [
                    'order_id'  => (int)$order_id,
                    'products'  => $products,
                    'position'  => intval($order->get_position()),
                    'date_add'  => $order->get_dateadd(),
                    'meta_data' => json_encode($meta_data_suppliers)
                ];


                wc_print_notices();
                wc_clear_notices();
                if ($order->get_position() !== 0 && $order->get_position() !== 2)
                    echo $Engine->render('@WC/demande/quotation-edit.html', ['quotation' => $quotation, 'position' => $order->get_position()]);

                break;
        endswitch;
    } else {
        $quotations = [];
        foreach ( $user_quotations as $order ) {
            $quotation = new \classes\fzQuotation($order->get_id());
            $quotations[] = [
                'order_id' => $quotation->get_id(),
                'position' => intval($quotation->get_position()),
                'date_add' => $quotation->get_dateadd()
            ];
        }

        wc_print_notices();
        wc_clear_notices();
        echo $Engine->render("@WC/demande/quotations-table.html", ['quotations' => $quotations]);
    }


}, 10);
add_action('woocommerce_account_faq_endpoint', function() {
    $url = home_url('/faq');
    $content = "<a href='{$url}' class='btn btn-theme radius-0'>Foire aux questions</a>";
    echo $content;
}, 10);

add_action('user_register', function ($user_id) {
    if (is_user_logged_in()) return false;
    $User = new WP_User(intval($user_id));
    $firstname = $lastname = "";

    if (!empty($_POST['firstname']) && !empty($_POST['lastname'])) {
        $firstname = sanitize_text_field($_POST['firstname']);
        $lastname = sanitize_text_field($_POST['lastname']);
        $result = wp_update_user([
            'ID' => intval($user_id),
            'first_name' => $firstname,
            'last_name'  => $lastname,
            'nickname'   => 'CL' . $user_id,
            'user_login' => 'CL' . $user_id
        ]);
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
        }
    }
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : '';
    update_field('address', sanitize_text_field($address), 'user_' . $user_id);
    update_field('phone', sanitize_text_field($phone), 'user_' . $user_id);
    update_field('client_reference', "CL{$User->ID}", 'user_' . $user_id);

    $user_customer = new WC_Customer(intval($user_id));
    /**
     * Role de l'utiisateur
     * Particulier ou entreprise
     */
    $role = sanitize_text_field($_REQUEST['role']);

    // Si le compte est une entreprise
    if ($role === 'company') {
        $fields = ['stat', 'nif', 'rc', 'cif'];
        foreach ($fields as $field) {
            $val = sanitize_text_field($_REQUEST[$field]);
            update_field($field, $val, 'user_' . $user_id);
        }
        update_field('company_name', $company_name, 'user_' . $user_id);
        // Ajouter un statut Professionnel ou revendeur
        // par default: En attente
        update_field('company_status', 'pending', 'user_' . $user_id);
    }

    // Si le compte est particulier
    if ($role === 'particular') {
        $fields = ['cin', 'date_cin'];
        foreach ($fields as $field) {
            $val = sanitize_text_field($_REQUEST[$field]);
            update_field($field, $val, 'user_' . $user_id);
        }
        update_field('company_status', false, 'user_' . $user_id);
    }

    // Ajouter le role du client
    $User->set_role("fz-{$role}");

    // Update customer woocommerce user field
    $zip = sanitize_text_field($_REQUEST['postal_code']);
    $city = sanitize_text_field($_REQUEST['city']);
    $user_customer->set_billing_location('MG', '', $zip, $city);
    $user_customer->set_billing_email($User->user_email);
    $user_customer->set_billing_company($company_name);
    $user_customer->set_billing_address_1($address);
    $user_customer->set_billing_first_name($firstname);
    $user_customer->set_billing_last_name($lastname);
    $user_customer->set_billing_address($address);
    $user_customer->set_billing_phone($phone);

    $user_customer->set_shipping_location('MG', '', $zip, $city);
    $user_customer->set_shipping_address_1($address);
    $user_customer->set_shipping_first_name($firstname);
    $user_customer->set_shipping_last_name($lastname);
    $user_customer->set_shipping_company($company_name);

    $user_customer->save_data();

});

add_action('wp_loaded', function() {

});

add_action('delete_user', function ($user_id) {
    $user_obj = get_userdata($user_id);
    $roles = $user_obj->roles;
    if (in_array('fz-supplier', $roles)) {
        // Effacer tous ces articles
        $args = [
            'post_type' => "fz_product",
            'post_type' => "any",
            "numberposts" => -1,
            "meta_query"  => [
                [
                    "key" => 'user_id',
                    "value"   => $user_id,
                    "compare" => "="
                ]
            ]
        ];

        $post_articles = get_posts($args);
        foreach ( $post_articles as $post ) {
            wp_delete_post($post->ID, true);
        }

        // Ne pas effacer les demandes ou les commandes
    }
}, 10, 1);

add_action('woocommerce_thankyou', 'fz_order_received', 10, 1);
function fz_order_received ($order_id)
{
    if (!is_user_logged_in()) return false;
    $User = wp_get_current_user();

    $order = new WC_Order(intval($order_id));
    $items = $order->get_items(); // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item.html (WC_Order_Item_Product)
    update_field('position', 0, intval($order_id));
    update_field('date_add', date_i18n('Y-m-d H:i:s'), intval($order_id));
    update_field('user_id', $User->ID, intval($order_id));

    foreach ( $items as $item_id => $item ) {
        wc_add_order_item_meta(intval($item_id), 'status', 0);
        wc_add_order_item_meta(intval($item_id), 'suppliers', null);
    }

    // Envoyer un mail aux administrateurs
    do_action('fz_received_order', $order_id);

}

// Cette action permet d'ajouter des meta donnée sur un post S.A.V pendant
// l'enregistrement dans la base de donnée
add_action('acf/save_post', function ($post_id) {
    if (!is_user_logged_in()) return;
    if (get_post_type($post_id) !== 'fz_sav') return;
    $User = wp_get_current_user();
    update_post_meta($post_id, 'sav_auctor', $User->ID);
    update_post_meta($post_id, 'sav_reference', "SAV" . $post_id);

    $product_name = get_field('product', $post_id);
    $product_mark = get_field('mark', $post_id);

    wp_update_post(['ID' => $post_id, 'post_title' => "#{$post_id} - {$product_name} - {$product_mark}"]);

    // Envoyer un email aux administrateur
    do_action('fz_insert_sav', $post_id);
});

add_action('wp_loaded', function() {

});