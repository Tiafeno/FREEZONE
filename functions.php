<?php

use classes\FZ_Quote;

require_once get_stylesheet_directory() . '/vendor/autoload.php';
require_once get_stylesheet_directory() . '/inc/fz-mail.php';
require_once get_stylesheet_directory() . '/inc/fz-functions.php';

define('freezone_phone_number', ' +261 34 86 319 90 / +261 32 53 408 03 / +261 33 82 589 08');
define('freezone_phone_fix_number', ' +261 20 24 292 31');

add_action('wp_enqueue_scripts', function () {
    $theme = wp_get_theme('freezone');
    wp_enqueue_style('yozi-child-theme', get_stylesheet_directory_uri() . '/style.css', [], $theme->get('Version'));
    wp_enqueue_script('fz-custom', get_stylesheet_directory_uri() . '/assets/js/fz-custom.js', ['jquery'], null, '0.0.3');

    wp_register_style('owlCarousel', get_stylesheet_directory_uri() . '/assets/js/owlcarousel/assets/owl.carousel.min.css', '', '2.0.0');
    wp_register_style('owlCarousel-green', get_stylesheet_directory_uri() . '/assets/js/owlcarousel/assets/owl.theme.green.min.css', '', '2.0.0');
    wp_register_script('owlCarousel', get_stylesheet_directory_uri() . '/assets/js/owlcarousel/owl.carousel.min.js', ['jquery'], '2.0.0', true);
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
    add_rewrite_endpoint('savs', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('gd', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('faq', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('pdf', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('catalogue', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('stock-management', EP_PERMALINK | EP_PAGES);
    add_rewrite_endpoint('demandes', EP_PERMALINK | EP_PAGES);
    add_filter('query_vars', function ($vars) {
        $vars[] = 'stock-management';
        $vars[] = 'demandes';
        $vars[] = 'faq';
        $vars[] = 'savs';
        $vars[] = 'gd';
        return $vars;
    }, 0);

    add_rewrite_tag('%componnent%', '([^&]+)');
    add_rewrite_tag('%module%', '([^&]+)');
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
        if ($post_type === 'product') :
            $get_articles_sql = <<<SQL
SELECT ID FROM {$wpdb->posts} WHERE post_type = 'fz_product' 
  AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE CONVERT(LOWER(`meta_key`) USING utf8mb4) = 'product_id' 
    AND meta_value = $post_id)
SQL;
            $results = $wpdb->get_results($get_articles_sql);
            foreach ( $results as $post ) {
                wp_delete_post(intval($post->ID), true);
            }

        endif;
    }, 10, 1);
}, 10);

add_filter('woocommerce_account_menu_items', function ($items) {
    //$logout = $items['customer-logout'];
    unset($items['customer-logout']);
    unset($items['orders']);
    $User = wp_get_current_user();
    if (in_array('fz-supplier', $User->roles)) {
        unset($items['edit-address']);
        $items['stock-management'] = 'Gestion de stock';
    } else {
        unset($items['stock-management']);
        $items['demandes'] = "Demandes";
        $items['catalogue'] = "Prestations";
        $items['savs'] = "S.A.V";
        $items['gd'] = "Petites annonces";
        $items['faq'] = "FAQ";
        //$items['pdf'] = "PDF";
    }
    //$items['customer-logout'] = $logout;
    return $items;
}, 999);

// enlever l'etat/Comite dans le formulaire de livraison
add_filter('woocommerce_default_address_fields', function ($fields) {
    unset($fields['state']);
    return $fields;
}, 999);

// Filtre pour le formulaire de commande ou demande
add_filter('woocommerce_checkout_fields', function ($fields) {

    $fields['billing']['billing_address_1']['label'] = 'Adresse';
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
    //unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_email']);
    unset($fields['billing']['billing_phone']);
    //unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_first_name']);
    //unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);

    $fields['shipping']['shipping_address_1']['label'] = 'Adresse';

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

    //unset($fields['shipping']['shipping_country']);
    //unset($fields['shipping']['shipping_first_name']);
    //unset($fields['shipping']['shipping_last_name']);
    unset($fields['shipping']['shipping_company']);
    unset($fields['shipping']['shipping_address_1']);
    unset($fields['shipping']['shipping_address_2']);
    //unset($fields['shipping']['shipping_city']);
    //unset($fields['shipping']['shipping_postcode']);
    unset($fields['shipping']['shipping_phone']);
    unset($fields['shipping']['shipping_email']);
    unset($fields['shipping']['shipping_state']);


    // Remplir automatiquement les champs pour l'étape de la demande

    return $fields;
}, 9999);

add_filter('woocommerce_default_address_fields', 'disable_address_fields_validation', 999);
function disable_address_fields_validation ($address_fields_array)
{
    return $address_fields_array;
}

// Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
add_action('woocommerce_account_stock-management_endpoint', function () {
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
                $fz_product = new \classes\fzProduct((int)$article_id);
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
                        update_post_meta($article_id, '_fz_quantity', intval($stock));
                        update_field('date_review', date_i18n('Y-m-d H:m:s'), $article_id);
                        wc_add_notice("Article mis à jour avec succès", 'success');
                    }
                }

                wc_print_notices();
                echo $Engine->render('@WC/stock/article-edit.html', [
                    'article' => $fz_product,
                    'back_link' => wc_get_account_endpoint_url('stock-management')
                ]);
                wc_clear_notices();
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

                // Vérifier si une formulaire est définie
                if ($_POST) {
                    $price = isset($_POST['price']) ? sanitize_text_field($_POST['price']) : 0;
                    $stock = isset($_POST['stock']) ? sanitize_text_field($_POST['stock']) : 0;
                    $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : 0;
                    $garentee = isset($_POST['garentee']) ? sanitize_text_field($_POST['garentee']) : 0;

                    // Vérifier si le produit existe déja
                    $verify_product_exist_args = [
                        'post_type' => 'fz_product',
                        'post_status' => 'any',
                        'posts_per_page' => 1,
                        'meta_query' => [
                            [
                                'key' => 'user_id',
                                'value' => intval($User->ID)
                            ],
                            [
                                'key' => 'product_id',
                                'value' => intval($product_id)
                            ]
                        ]
                    ];
                    $product_exists = get_posts($verify_product_exist_args);
                    if (!$product_exists) {
                        $product = get_post((int)$product_id);
                        $product_cat = wp_get_post_terms((int)$product_id, 'product_cat', ['fields' => 'ids']);
                        if ($price && $stock && $product_id) {
                            $result = wp_insert_post([
                                'post_type' => 'fz_product',
                                'post_status' => 'publish',
                                'post_title' => $product->post_title
                            ], true);

                            if (is_wp_error($result)) {
                                wc_add_notice($result->get_error_message(), 'error');
                            } else {
                                update_field('price', intval($price), $result);
                                update_field('total_sales', intval($stock), $result);
                                update_field('user_id', intval($User->ID), $result);
                                update_field('product_id', (int)$product_id, $result);
                                update_field('date_review', date_i18n('Y-m-d H:i:s'), $result);
                                update_field('date_add', date_i18n('Y-m-d H:i:s'), $result);
                                update_post_meta($result, '_fz_garentee', $garentee);
                                wp_set_post_terms($result, $product_cat, 'product_cat');
                                // Envoyer un mail au administrateur
                                do_action('fz_insert_new_article', $result);

                                //wp_redirect(wc_get_account_endpoint_url('stock-management'));
                                wc_add_notice("Article <b>« {$product->post_title} »</b> ajouter avec succès", 'success');
                            }
                        } else {
                            wc_add_notice("Une erreur s'est produite pendant le traitement de donnée", 'error');
                        }
                    } else {
                        wc_add_notice("Cette article existe déja dans votre catalogue", 'notice');
                    }
                } // .end POST

                wc_print_notices();
                echo $Engine->render('@WC/stock/article-new.html', [
                    'products' => $fz_model->get_products(),
                    'back_link' => wc_get_account_endpoint_url('stock-management')
                ]);
                wc_clear_notices();
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
        // Afficher tous les articles du fournisseur
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
            'post_type' => "fz_product",
            'post_status' => ['pending', 'publish'],
            'posts_per_page' => $posts_per_page,
            'paged' => $paged
        ];
        $query = new WP_Query($args);
        $pagination = '<div class="apus-pagination"><ul class="page-numbers">';
        $pagination .= paginate_links([
            'base' => @add_query_arg('pa_', '%#%'),
            'format' => '?pa_=%#%',
            'current' => max(1, get_query_var('pa_')),
            'type' => 'list',
            'current' => $paged,
            'total' => $query->max_num_pages

        ]);
        $pagination .= '</ul></div>';
        $articles = [];
        foreach ( $query->posts as $post ) {
            $articles[] = new \classes\fzProduct($post->ID);
        }

        wc_print_notices();
        echo $Engine->render('@WC/stock/article-table.html', [
            'products' => $articles,
            'new_article_link' => wc_get_account_endpoint_url('stock-management') . '?componnent=new'
        ]);
        echo $pagination;
        wc_clear_notices();
    }
}, 10);

// Service après vente - S.A.V
add_action('woocommerce_account_savs_endpoint', function () {
    global $Engine, $wp_query;
    // index: is default module
    $current_module = isset($wp_query->query_vars['module']) ? $wp_query->query_vars['module'] : 'index';
    $sav_url = '/sav'; // Cette url est réservé pour la publication des services àpres vente
    $user_id = get_current_user_id();
    $sav_id = null;


    // same as middleware
    if (isset($wp_query->query_vars['componnent'])) {
        $componnent = sanitize_text_field($wp_query->query_vars['componnent']);
        $sav_id = (int)sanitize_text_field($wp_query->query_vars['id']);
        switch ($componnent) {
            case 'revival':
                // Envoyer un email au responsable (David & Nant.)
                if (!isset($_COOKIE[ 'freezone_revival-' . $sav_id ])) {
                    do_action('fz_sav_revival_mail', $sav_id);
                    setcookie('freezone_revival-' . $sav_id, true, time() + 86400); // 1 day
                    wc_add_notice("Rappel anvoyer avec succès au responsables", 'success');
                } else {
                    wc_add_notice("Vous avez déja envoyer une rappel", 'notice');
                }
                break;

            case 'print':
                break;

            default:
                wc_add_notice("Parametre composant inconnue '{$componnent}'", 'error');
                break;
        }
    }

    // Gere les views template
    switch ($current_module) {
        case 'print':
            if (is_null($sav_id)) continue;
            $fzSav = new \classes\fzSav($sav_id, true);
            wc_print_notices();
            echo $Engine->render(
                '@WC/savs/sav-print.html',
                [
                    'content' => $fzSav,
                    'sav_url' => $sav_url,
                    'prestations_url' => wc_get_account_endpoint_url('catalogue'),
                    'sav_endpoint_url' => wc_get_account_endpoint_url('savs'),
                ]
            );
            wc_clear_notices();
            break;
        case 'index':
        default:
            // Récuperer tous les demandes SAV effectuer par le client 
            $args = [
                'post_type' => 'fz_sav',
                'post_status' => "any",
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'customer',
                        'value' => $user_id
                    ]
                ]
            ];
            $the_query = new WP_Query($args);
            $savs = array_map(function ($sav) {
                $fzSav = new \classes\fzSav($sav->ID, true);
                return $fzSav;
            }, $the_query->posts);

            wc_print_notices();
            echo $Engine->render(
                '@WC/savs/sav-lists.html',
                [
                    'savs' => $savs,
                    'sav_url' => $sav_url,
                    'prestations_url' => wc_get_account_endpoint_url('catalogue'),
                    'sav_endpoint_url' => wc_get_account_endpoint_url('savs'),
                ]
            );
            wc_clear_notices();
            break;
    }
}, 10);

// Menu catalogue dans l'espace client
add_action('woocommerce_account_catalogue_endpoint', function () {
    wp_enqueue_script('sweetalert2@8', "https://cdn.jsdelivr.net/npm/sweetalert2@8", ['jquery']);
    // https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.js
    wp_enqueue_script('vue', "https://cdn.jsdelivr.net/npm/vue/dist/vue.js", ['jquery']);
    wp_enqueue_script('account-prestation', get_stylesheet_directory_uri() . '/assets/js/account-prestations.js', ['vue', 'jquery'], rand(45, 90));
    wp_localize_script('account-prestation', 'account_opt', [
        'rest_url' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);

    // Render template
    // Don't use twig because interpolation syntaxe is same for VueJS
    include_once get_stylesheet_directory() . '/templates/wc/catalogues/catalogue.html';
});

// Demande ou devis
add_action('woocommerce_account_demandes_endpoint', function () {
    global $Engine, $wp_query;

    $shop_url = get_permalink(wc_get_page_id('shop'));
    $user_id = get_current_user_id();
    // Executer ici la mise a jours du formulaire, si un formulaire est envoyer
    do_action("woocommerce_before_edit_address_form_billing");
    $clientInstance = \classes\fzClient::initializeClient($user_id);
    // Verification de securite
    if (!in_array($clientInstance->get_role(), ["fz-particular", "fz-company"])) {
        wc_add_notice("Vous n'avez pas l'autorisation nécessaire pour voir les contenues de cette page", "error");
        wc_print_notices();
        wc_clear_notices();
        return false;
    }
    $client = $clientInstance->get_client();
    $form = false;
    // Si les donnee du client ne sont pas correct, On essai de lui faire remplire le formulaire
    if ($clientInstance->get_role() === "fz-company" && (!$client->stat && !$client->nif && !$client->sector_activity)) {
        // Si le client est une entreprise
        $form = true;
    }

    if ($clientInstance->get_role() === "fz-particular" && (!$client->cin && !$client->date_cin)) {
        // Si le client est un particulier
        $form = true;
    }

    if ($form) {
        wc_add_notice("Pour que vous puissiez y accéder a votre demande nous vous prions de bien vouloir remplir les informations suivants", "notice");
        wc_print_notices();
        do_action('woocommerce_account_edit-address_endpoint', 'billing');
        return false;
    }

    // https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
    $user_quotations = wc_get_orders(['customer_id' => $user_id]);
    if (empty($user_quotations)) {
        $content = '<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">';
        $content .= '<a class="woocommerce-Button button" href="' . $shop_url . '">';
        $content .= 'Voir les catalogues</a> Aucune demande n\'a été passée.	</div>';
        echo $content;
        return false;
    }

    if (isset($wp_query->query_vars['componnent'])) {
        $componnent = sanitize_text_field($wp_query->query_vars['componnent']);
        $order_id = $wp_query->query_vars['id'];
        $quotation = new \classes\FZ_Quote(intval($order_id));
        switch ($quotation->get_position()) {
            case 0:
                wc_add_notice("Votre demande est en cours de validation. Veuillez réessayer plus tard", "notice");
                break;
            case 3:
                wc_add_notice("Vous ne pouvez plus modifier cette demande", "notice");
                break;
        }
        switch ($componnent):
            case 'update':
            case 'confirmaction':
                wp_enqueue_script('underscore');
                wp_enqueue_script('sweetalert2@8', "https://cdn.jsdelivr.net/npm/sweetalert2@8", ['jquery']);
                wp_enqueue_script('quotation-update', get_stylesheet_directory_uri() . '/assets/js/quotation-update.js', ['jquery']);
                // Formulaire dans quotation-update.html
                $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : null;
                if (wp_verify_nonce($nonce, 'confirmaction')) {
                    $value = stripslashes($_REQUEST['value']);
                    $value = intval($value);
                    $order = wc_get_order(intval($order_id));
                    $position = get_field('position', $order->get_id());
                    $status = null;
                    switch ($value):
                        case 1:
                            // Demande du client accepter
                            update_field('position', 3, $order->get_id());
                            $order->update_status('completed');
                            $status = 'completed';
                            wc_add_notice("Demande accepter avec succès", 'notice');
                            break;
                        case 0:
                            // Rejeter la demande du client
                            update_field('position', 2, $order->get_id());
                            $status = 'rejected';
                            wc_add_notice("Demande rejetée avec succès", 'error');
                            break;
                    endswitch;
                    // Envoyer un mail au administrateur
                    if (!is_null($status) && !is_numeric($position))
                        do_action('complete_order', $order->get_id(), $status);
                }
                wc_print_notices();
                //Redefinir l'objet de la demande pour:
                //Corriger la valeur de la 'position' pendant la modification (Refuser et Rejeter)
                $quotation = new \classes\FZ_Quote(intval($order_id));
                $quotation_params = [
                    'order_id' => (int)$order_id,
                    'lines' => $quotation->fz_items(),
                    'lines_zero' => $quotation->fz_items_zero(),
                    'position' => intval($quotation->get_position()),
                    'date_add' => $quotation->date_add()
                ];
                /** ************************ */
                echo $Engine->render('@WC/demande/quotation-update.html', [
                    'quote' => $quotation_params,
                    'order' => $quotation,
                    'download_url' => wc_get_account_endpoint_url('pdf'),
                    'define' => [
                        'nonce' => wp_create_nonce('confirmaction'),
                        'min_cost_with_transport' => $quotation->get_min_cost_with_transport(),
                        'cost_transport' => $quotation->get_cost_transport()
                    ]
                ]);
                break;
        endswitch;

        // Effacer les notices
    } else {
        $quotations = [];
        foreach ( $user_quotations as $order ) {
            $quotation = new \classes\FZ_Quote($order->get_id());
            $quotations[] = [
                'order_id' => $quotation->get_id(),
                'position' => intval($quotation->get_position()),
                'date_add' => $quotation->date_add()
            ];
        }
        wc_print_notices();
        $content = '<div class="woocommerce-message woocommerce-message--info woocommerce-Message woocommerce-Message--info woocommerce-info">';
        $content .= '<a class="woocommerce-Button button" href="' . $shop_url . '">';
        $content .= 'Poursuivre ma demande</a> Vous pouvez toujours effectuer une demande </div>';
        echo $content;
        echo $Engine->render("@WC/demande/quotations-table.html", ['quotations' => $quotations]);
    }

    wc_clear_notices();
}, 10);

// Cette action est utiliser pour télécharger le PDF
add_action('woocommerce_account_pdf_endpoint', function () {
    global $Engine;
    $order = null;
    $error = false;
    if ($_GET && isset($_GET['order_id']) && !empty($_GET['order_id'])) {
        $order_id = intval($_GET['order_id']);
        $order = new FZ_Quote($order_id);
    } else {
        $error = true;
        wc_add_notice('Parametre manquant (order_id)', 'error');
    }
    if ($error) {
        wc_print_notices();
        wc_clear_notices();
        return false;
    }
    wp_enqueue_style('poppins', "https://fonts.googleapis.com/css?family=Poppins:300,400,700,800");
    wp_enqueue_script('html2pdf', get_stylesheet_directory_uri() . '/assets/js/html2pdf/html2pdf.bundle.min.js', null, "0.9.1");
    wp_enqueue_script('download-pdf', get_stylesheet_directory_uri() . '/assets/js/download-pdf.js', ['html2pdf'], '1.0.0', true);
    wp_localize_script('download-pdf', 'Generator', [
        'order_id' => $order_id
    ]);
    $customer = new WC_Customer($order->get_customer_id());
    // Get responsible if exist
    $responsible = $customer->meta_exists('responsible') ? $customer->get_meta('responsible', true) : null;
    $responsible = is_null($responsible) ? null : new WP_User(intval($responsible));
    $items = $order->fz_items();
    $items_zero = $order->fz_items_zero();
    // Afficher le template
    echo $Engine->render('@WC/pdf/download-template.html', [
        'order' => $order,
        'responsible' => $responsible,
        'items' => $items,
        'items_zero' => $items_zero,
        'customer' => $customer,
        'hlp' => [
            'theme_url' => get_stylesheet_directory_uri()
        ],
        'define' => [
            'min_cost_with_transport' => $order->get_min_cost_with_transport(),
            'cost_transport' => $order->get_cost_transport()
        ]
    ]);
    wc_clear_notices();
}, 10);

/**
 * Afficher les foire aux question pour les client dans leur espace client
 * Utiliser visual composer (accordion)
 */
add_action('woocommerce_account_faq_endpoint', function () {
    global $wpdb;
    if (!is_user_logged_in()) {
        echo "Vous n'avez pas l'autorisation necessaire pour voir les contenues de cette page";
        return true;
    }
    $User = wp_get_current_user();
    /**
     * 1: Company (fz-company)
     * 2: Particulier (fz-particular)
     */
    $category = in_array('fz-company', $User->roles) ? 1 : 2;
    $sql = <<<SQL
SELECT * FROM $wpdb->posts WHERE post_status = "publish" 
    AND post_type = "fz_faq_client" 
    AND ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = "faq_category" AND meta_value = $category)
SQL;
    $results = $wpdb->get_results($sql);
    $shortcode = "[vc_row][vc_column][vc_tta_accordion]";
    foreach ( $results as $result ) {
        $title = apply_filters('the_title', $result->post_title, $result->ID);
        $shortcode .= "[vc_tta_section title=\"{$title}\" tab_id='faq-{$result->ID}'][vc_column_text] {$result->post_content} [/vc_column_text]
            [/vc_tta_section]";
    }
    $shortcode .= "[/vc_tta_accordion][/vc_column][/vc_row]";
    echo do_shortcode($shortcode);
}, 10);

add_action('woocommerce_account_gd_endpoint', function () {
    global $Engine;
    if ($_GET) {
        if (isset($_GET['module'])) {
            $module = $_GET['module'];
            switch ($module) {
                case 'edit':
                    do_action('fz_annonce_edit', (int)$_GET['id']);
                    return;
                    break;
                case 'update':
                    do_action('fz_annonce_update', (int)$_GET['id']);
                    return;
                    break;
                case 'delete':
                    do_action('fz_annonce_delete', (int)$_GET['id']);
                    break;
                default:
                    break;
            }
        }
    }

    $user = wp_get_current_user();
    $args = [
        'post_type' => 'good-deal',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'gd_author',
                'value' => $user->ID
            ]
        ]
    ];
    $the_query = new WP_Query($args);
    $good_deals = array_map(function ($good_deal) {
        $gd = new \classes\fzGoodDeal($good_deal->ID);
        return $gd;
    }, $the_query->posts);
    echo $Engine->render('@WC/gd/gd-lists.html', ['gooddeals' => $good_deals]);
}, 10);

// Action for annonce
add_action('fz_annonce_edit', function ($id) {
    wc_print_notices();
    wc_clear_notices();
    if (!is_numeric($id)) return;
    global $Engine;
    $good_deal = new \classes\fzGoodDeal($id);
    echo $Engine->render('@WC/gd/gd-edit.html', [
        'annonce' => $good_deal,
        'categories' => \Services\fzServices::get_categories()
    ]);
}, 10, 1);

add_action('fz_annonce_update', function ($id) {
    if (!is_numeric($id)) return;
    // Update annonce
    $error = false;
    if ($_POST) {
        try {
            $good_deal = new \classes\fzGoodDeal($id);
            $good_deal->set_title($_POST['title']);
            $good_deal->set_description($_POST['description']);
            $good_deal->set_price((int)$_POST['price']);
            $good_deal->set_categorie(intval($_POST['categorie']));
        } catch (\Exception $th) {
            $error = true;
            wc_add_notice($th->getMessage(), 'error');
        }
    }

    if (!$error) {
        wc_add_notice("Mise a jour effectuer avec succes", 'info');
    }

    // afficher le formulaire de modification
    do_action('fz_annonce_edit', $id);
}, 10, 1);

add_action('fz_annonce_delete', function ($id) {
    if (!is_numeric($id)) return;
    //(WP_Post|false|null) Post data on success, false or null on failure.
    $response = wp_delete_post($id, true);
    if (false === $response || is_null($response)) {
        wc_add_notice("Une erreur c'est produit pendant la suppression de l'annonce", 'error');
        do_action('fz_annonce_edit', $id);
        return;
    }
    $url = wc_get_account_endpoint_url('gd');
    wp_redirect(add_query_arg([], $url));
}, 10, 1);

/**
 * Ajouter un client via le formulaire (form-login.php)
 */
add_action('user_register', function ($user_id) {
    if (is_user_logged_in()) return false;
    $user_data = get_userdata(intval($user_id));
    /**
     * Role de l'utilisateur
     * Particulier ou entreprise
     */
    $role = sanitize_text_field($_REQUEST['role']);
    $company_name = isset($_REQUEST['company_name']) ? $_REQUEST['company_name'] : '';
    if (!empty($_REQUEST['lastname'])) {
        $firstname = sanitize_text_field($_REQUEST['firstname']);
        $lastname = sanitize_text_field($_REQUEST['lastname']);
        $nickname = $role === 'company' ? 'CL' . sanitize_title($company_name, '') . '-' . $user_id : 'CL' . $user_id;
        $result = wp_update_user([
            'ID' => intval($user_id),
            'first_name' => $firstname ? $firstname : '',
            'last_name' => $lastname,
            'nickname' => $nickname,
            'user_login' => $nickname
        ]);
        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            return false;
        }
    } else {
        wc_add_notice("Le champ Nom est obligatoire", 'error');
        return false;
    }
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : '';
    $sector_activity = isset($_POST['sector_activity']) ? $_POST['sector_activity'] : '';
    update_field('address', sanitize_text_field($address), 'user_' . $user_id);
    update_field('phone', sanitize_text_field($phone), 'user_' . $user_id);
    update_field('client_reference', "CL{$user_id}", 'user_' . $user_id);
    $user_customer = new WC_Customer(intval($user_id));
    // Si le compte est une entreprise
    if ($role === 'company') {
        $fields = ['stat', 'nif', 'rc', 'cif'];
        foreach ( $fields as $field ) {
            $val = sanitize_text_field($_REQUEST[ $field ]);
            update_field($field, $val, 'user_' . $user_id);
        }
        update_field('company_name', $company_name, 'user_' . $user_id);
        // Ajouter un statut Professionnel ou revendeur
        // par default: En attente
        update_field('company_status', 'pending', 'user_' . $user_id);
        // Ajouter le secteur d'activité pour l'entreprise
        update_user_meta($user_id, 'sector_activity', $sector_activity);
    }
    // Si le compte est particulier
    if ($role === 'particular') {
        $fields = ['cin', 'date_cin'];
        foreach ( $fields as $field ) {
            $val = sanitize_text_field($_REQUEST[ $field ]);
            update_field($field, $val, 'user_' . $user_id);
        }
        // Compte ni Professionnel, ni Revendeur
        update_field('company_status', false, 'user_' . $user_id);
        // Mettre le compte particulier en attente par default
        // 0: not pending, 1: Pending
        update_user_meta($user_id, "fz_pending_user", 1);
    }
    // Ajouter le role du client
    $user_role = "fz-{$role}";
    $user_data->set_role($user_role);
    add_user_meta($user_id, "fz_pending_user", 1, true); // Mettre en attente
    add_user_meta($user_id, "ja_disable_user", 0, true); // Ne pas désactiver l'utilisateur
    // Envoyer un email de notification pour l'administrateur
    do_action('fz_new_user', $user_id, $user_role);
    // Update customer woocommerce user field
    $zip = sanitize_text_field($_REQUEST['postal_code']);
    $city = sanitize_text_field($_REQUEST['city']);
    // Billing (Facturation)
    $user_customer->set_billing_location('MG', '', $zip, $city);
    $user_customer->set_billing_email($user_data->user_email);
    $user_customer->set_billing_company($company_name);
    $user_customer->set_billing_address_1($address);
    $user_customer->set_billing_address_2($address);
    $user_customer->set_billing_first_name($firstname);
    $user_customer->set_billing_last_name($lastname);
    $user_customer->set_billing_address($address);
    $user_customer->set_billing_phone($phone);
    $user_customer->set_billing_postcode($postal_code);
    // Shipping
    $user_customer->set_shipping_location('MG', '', $zip, $city);
    $user_customer->set_shipping_address_1($address);
    $user_customer->set_shipping_address_2($address);
    $user_customer->set_shipping_first_name($firstname);
    $user_customer->set_shipping_last_name($lastname);
    $user_customer->set_shipping_company($company_name);
    $user_customer->set_shipping_postcode($postal_code);
    $user_customer->save_data();
});

add_action('wp_loaded', function () {
    //update_field('position', 1, 1431);
    add_action('wp_logout', 'auto_redirect_after_logout');
    function auto_redirect_after_logout ()
    {
        wp_redirect(home_url());
        exit();
    }

    add_filter('wp_nav_menu_items', function ($items, $args) {
        // Ajouter un lien de déconnection ou connexion dans le menu
        if ($args->theme_location == 'top-menu') {
            if (is_user_logged_in()) {
                $items .= '<li class="right"><a href="' . wp_logout_url() . '">' . __("Log Out") . '</a></li>';
            } else {
                $items .= '<li class="right"><a href="' . wp_login_url(get_permalink()) . '">' . __("Log In") . '</a></li>';
            }
        }
        return $items;
    }, 10, 2);
});

add_action('delete_user', function ($user_id) {
    $user_obj = get_userdata($user_id);
    $roles = $user_obj->roles;
    if (in_array('fz-supplier', $roles)) {
        $args = [
            'post_type' => "fz_product",
            'post_type' => "any",
            "numberposts" => -1,
            "meta_query" => [
                [
                    "key" => 'user_id',
                    "value" => $user_id,
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

add_action('woocommerce_thankyou', function ($order_id) {
    if (!is_user_logged_in()) return false;
    $User = wp_get_current_user();
    $order = new WC_Order(intval($order_id));
    $items = $order->get_items(); // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item.html (WC_Order_Item_Product)
    update_field('position', 0, intval($order_id)); // Mettre l demande en attente par default
    update_field('date_add', date_i18n('Y-m-d H:i:s'), intval($order_id));
    update_field('user_id', $User->ID, intval($order_id));
    // Utiliser cette valeur pour classifier les commandes des clients (Entreprise ou Particulier)
    update_post_meta(intval($order_id), 'client_role', $User->roles[0]);
    foreach ( $items as $item_id => $item ) {
        wc_add_order_item_meta(intval($item_id), 'status', 0);
        wc_add_order_item_meta(intval($item_id), 'suppliers', json_encode([]));
    }
    // Envoyer un mail aux administrateurs
    do_action('fz_received_order', $order_id);
    // Mettre les fournisseurs qui posséde ces produits en attente d'envoie (mail)
    $apiSupplier = new apiSupplier();
    $request = new WP_REST_Request();
    $request->set_param('action', 'review');
    $results = $apiSupplier->action_collect_suppliers($request);
    $data = $results['data'];
    if (!empty($data) && is_array($data)) {
        foreach ( $data as $item ) {
            // TODO: Verifier si le produit de cette fournisseurs est déja à jour
            update_user_meta((int)$item['id'], 'send_mail_review_date', null);
        }
    }
}, 10, 1);

// Cette action permet d'ajouter des meta donnée sur un post S.A.V pendant
// l'enregistrement dans la base de donnée
add_action('acf/save_post', function ($post_id) {
    if (!is_user_logged_in()) return;
    if (get_post_type($post_id) !== 'fz_sav') return;
    $User = wp_get_current_user();
    $product_name = get_field('product', $post_id);
    $product_mark = get_field('mark', $post_id);
    wp_update_post(
        [
            'ID' => $post_id,
            'post_title' => "#{$post_id} - {$product_name} - {$product_mark}"
        ]
    );
    //update_post_meta($post_id, 'sav_auctor', $User->ID);
    //update_post_meta($post_id, 'sav_reference', "SAV" . $post_id);
    // Envoyer un email aux administrateur
    do_action('fz_insert_sav', $post_id);
});

add_action('wp_loaded', function () {
});
