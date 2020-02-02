<?php
require_once 'model/fz-model.php';

require_once "lib/underscore.php";

require_once 'shortcodes/after-sales-service.php';
require_once 'shortcodes/slider.php';

require_once 'classes/fzServices.php';
require_once 'classes/fzRoles.php';
require_once 'classes/fzSav.php';
require_once 'classes/fzMailing.php';
require_once 'classes/fzPTFreezone.php';
require_once 'classes/fzParticular.php';
require_once 'classes/fzCompany.php';
require_once 'classes/fzSupplier.php';
require_once 'classes/fzClient.php';
require_once 'classes/fzProduct.php';
require_once 'classes/fzQuote.php';
require_once 'classes/fzItemOrder.php';
require_once 'classes/fzGoodDeal.php';
require_once 'classes/fzCarousel.php';
require_once 'classes/fzCatalogue.php';

require_once 'api/v1/apiGoodDeal.php';
require_once 'api/v1/apiQuotation.php';
require_once 'api/v1/apiSupplier.php';
require_once 'api/v1/apiProduct.php';
require_once 'api/v1/apiFzProduct.php';
require_once 'api/v1/apiMail.php';
require_once 'api/v1/apiSav.php';
require_once 'api/v1/apiImport.php';
require_once 'api/v1/apiExport.php';
require_once 'api/fzAPI.php';

require_once 'cron/task-cron.php';

if (!defined('TWIG_TEMPLATE_PATH')) {
    define('TWIG_TEMPLATE_PATH', get_stylesheet_directory() . '/templates');
}

if (!defined('__SITENAME__')) {
    define('__SITENAME__', 'freezone');
}

if (function_exists('acf_add_options_page')) {
    $parent = acf_add_options_page([
        'page_title' => 'Parametre Freezone',
        'menu_title' => 'Parametre Freezone',
        'capability' => 'delete_users',
        'autoload' => true,
        'redirect' => false
    ]);
}

/**
 * *****************************************************************************
 *                              Variable global
 * *****************************************************************************
 */
$fz_model = new \model\fzModel();
$Engine = null;
try {
    $file_system = new Twig_Loader_Filesystem();
    $file_system->addPath(TWIG_TEMPLATE_PATH . '/vc', 'VC');
    $file_system->addPath(TWIG_TEMPLATE_PATH . '/shortcodes', 'SC');
    $file_system->addPath(TWIG_TEMPLATE_PATH . '/wc', 'WC');
    $file_system->addPath(TWIG_TEMPLATE_PATH . '/mail', 'MAIL');
    /** @var Object $Engine */
    $Engine = new Twig_Environment($file_system, [
        'debug' => true,
        'cache' => TWIG_TEMPLATE_PATH . '/cache',
        'auto_reload' => true,
    ]);
    $Engine->addFilter(new Twig_SimpleFilter('fakediscount', function ($item) {
        $has_discount = wc_get_order_item_meta($item->get_id(), 'has_discount', true);
        $fake_discount = wc_get_order_item_meta($item->get_id(), 'fake_discount', true);
        $has_discount = $has_discount ? boolval(intval($has_discount)) : true;

        return $has_discount ? $fake_discount : '';
    }));
    $Engine->addFilter(new Twig_SimpleFilter('wpoption', function ($field) {
        return get_option($field, 'N/A');
    }));
} catch (\Twig_Error_Loader $e) {
    echo $e->getRawMessage();
}

add_action('after_switch_theme', function () {
    if (has_action('fz_activate_theme')) {
        do_action('fz_activate_theme'); // Action Model (fz-model.php)
    }
    load_theme_textdomain(__SITENAME__, get_template_directory() . '/languages');
    /** @link https://codex.wordpress.org/Post_Thumbnails */
    add_theme_support('post-thumbnails');
    add_theme_support('category-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('title-tag');
    add_theme_support('custom-logo', [
        'height' => 100,
        'width' => 250,
        'flex-width' => true,
    ]);
    add_image_size('sidebar-thumb', 120, 120, true);
    add_image_size('homepage-thumb', 220, 180);
    add_image_size('singlepost-thumb', 590, 9999);
    /**
     * This function will not resize your existing featured images.
     * To regenerate existing images in the new size,
     * use the Regenerate Thumbnails plugin.
     */
    set_post_thumbnail_size(50, 50, ['center', 'center']); // 50 pixels wide by 50 pixels tall, crop from the center
});

// Désactiver l'access à la back-office pour les utilisateurs non admin
add_action('after_setup_theme', function () {
    show_admin_bar(current_user_can('administrator') ? true : false);
});


add_action('admin_init', function () {
    if (is_null(get_role('fz-supplier')) || is_null(get_role('fz-company')) || is_null(get_role('fz-particular'))) {
        \classes\fzRoles::create_roles();
    }
    if (is_user_logged_in()) {
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url('/');
        if (is_admin() && !defined('DOING_AJAX') && !current_user_can('administrator')) {
            exit(wp_redirect($redirect, 301));
        }
    }
    // Afficher les en-tete pour les marges
    add_filter('manage_fz_product_posts_columns', function ($columns) {
        $columns['marge'] = '% UF';
        $columns['marge_dealer'] = '% R.';
        $columns['marge_particular'] = '% P.';
        return $columns;
    });
    // Afficher les valeurs par colonne
    add_action('manage_fz_product_posts_custom_column', function ($column, $post_id) {
        if ($column === 'marge'):
            $marge = get_post_meta($post_id, '_fz_marge', true);
            $marge = $marge ? $marge : 0;
            echo "{$marge} %";
        endif;
        if ($column === 'marge_dealer'):
            $marge_dealer = get_post_meta($post_id, '_fz_marge_dealer', true);
            $marge_dealer = $marge_dealer ? $marge_dealer : 0;
            echo "{$marge_dealer} %";
        endif;

        if ($column === 'marge_particular'):
            $marge_dealer = get_post_meta($post_id, '_fz_marge_particular', true);
            $marge_dealer = $marge_dealer ? $marge_dealer : 0;
            echo "{$marge_dealer} %";
        endif;
    }, 10, 2);
}, 100);

// https://developer.wordpress.org/block-editor/tutorials/metabox/meta-block-2-register-meta/
$meta_args = array(
    'type'         => 'integer',
    'description'  => 'A meta key associated with a string meta value.',
    'single'       => true,
    'show_in_rest' => true,
);
register_post_meta( 'fz_product', '_fz_marge', $meta_args );
register_post_meta( 'fz_product', '_fz_marge_dealer', $meta_args );
register_post_meta( 'fz_product', '_fz_marge_particular', $meta_args );

add_action('init', function () {
    function search_products ()
    {
        $search_results = new WP_Query([
            's' => esc_sql($_REQUEST['q']),
            'post_type' => 'product',
            'post_status' => 'publish',
            //'ignore_sticky_posts' => 1,
            'posts_per_page' => 20
        ]);
        wp_send_json_success($search_results->posts);
    }

    add_action('wp_ajax_searchproducts', 'search_products');
    add_action('wp_ajax_nopriv_searchproducts', 'search_products');
    /**
     * Register the 'Custom Column' column in the importer.
     *
     * @param array $options
     * @return array $options
     */
    function add_column_to_importer ($options)
    {
        $options['attribute'] = 'Attribut';
        $options['attribute_value'] = 'Attribut valeur';
        return $options;
    }

    add_filter('woocommerce_csv_product_import_mapping_options', 'add_column_to_importer');

    /**
     * Add automatic mapping support for 'Custom Column'.
     * This will automatically select the correct mapping for columns named 'Custom Column' or 'custom column'.
     *
     * @param array $columns
     * @return array $columns
     */
    function add_column_to_mapping_screen ($columns)
    {
        // potential column name => column slug
        $columns['Attribut'] = 'attribute';
        $columns['Attribut valeur'] = 'attribute_value';
        return $columns;
    }

    add_filter('woocommerce_csv_product_import_mapping_default_columns', 'add_column_to_mapping_screen');

    /**
     * Process the data read from the CSV file.
     * This just saves the value in meta data, but you can do anything you want here with the data.
     *
     * @param WC_Product $object - Product being imported or updated.
     * @param array $data - CSV data read for the product.
     *
     * @return WC_Product $object
     */
    function process_import ($object, $data)
    {
        $attributes = $data['attribute'];
        $attribute_values = $data['attribute_value'];
        if (empty($attributes)) return $object;
        $attributes = explode(',', $attributes);
        $attribute_values = explode(',', $attribute_values);
        // Create woocommerce product
        $options = get_field('wc', 'option');
        $woocommerce = new Automattic\WooCommerce\Client(
            "http://{$_SERVER['SERVER_NAME']}", $options['ck'], $options['cs'],
            [
                'version' => 'wc/v3'
            ]
        );
        foreach ( $attributes as $key => $attr ) {
            if (empty($attr)) continue;
            $attr = stripslashes($attr);
            $attr = trim($attr);
            $attr_id = wc_attribute_taxonomy_id_by_name(ucfirst($attr)); // @return int
            if (0 === $attr_id) {
                // Crée une attribut
                $data = [
                    'name' => ucfirst($attr),
                    'type' => 'select',
                    'order_by' => 'menu_order',
                    'has_archives' => true
                ];

                $created_attribute_response = $woocommerce->post('products/attributes', $data);
                $attr_id = $created_attribute_response->id;
            }
            $product_id = $object->get_id();
            $product_response = $woocommerce->get("products/{$product_id}"); // Return rest product object
            $attributes = $product_response->attributes; // Return array of stdClass
            $value = ucfirst(trim($attribute_values[ $key ]));
            if (is_array($attributes)) {
                array_push($attributes, [
                    'id' => $attr_id,
                    'position' => 0,
                    'visible' => true,
                    'variation' => false, // for variative products in case you would like to use it for variations
                    'options' => [$value] // if the attribute term doesn't exist, it will be created
                ]);
            }

            $args = [
                'attributes' => $attributes
            ];
            $woocommerce->put("products/{$product_id}", $args);
        }
        //update_post_meta($object->get_id(), '_product_attributes', $attrs);
        return $object;
    }
    add_action('woocommerce_product_import_inserted_product_object', 'process_import', 10, 2);

    // Mise a jour d'adresse dans l'espace client
    add_action("woocommerce_before_edit_address_form_billing", function () {
        if (isset($_REQUEST['woocommerce-edit-address-nonce']) &&
            wp_verify_nonce($_REQUEST['woocommerce-edit-address-nonce'], 'woocommerce-edit_address')) {
            $customer_id = get_current_user_id();
            $fields = [['key' => 'billing_phone', 'field' => 'phone'], ['key' => 'billing_address_2', 'field' => 'address']];
            foreach ( $fields as $field ) {
                $val = sanitize_text_field($_REQUEST[ $field['key'] ]);
                update_field($field['field'], $val, 'user_' . $customer_id);
            }
            $role = \classes\fzClient::initializeClient($customer_id)->get_role();
            switch ($role) {
                case 'fz-company':
                    $acf_fields = ['nif', 'cif', 'stat', 'rc'];
                    foreach ( $acf_fields as $field ) {
                        if (!isset($_REQUEST[ $field ])) continue;
                        update_field($field, $_REQUEST[ $field ], 'user_'.$customer_id);
                    }
                    update_user_meta($customer_id, 'sector_activity', intval($_REQUEST['sector_activity']));
                    break;
                case 'fz-particular':
                    $acf_fields = ['cin', 'date_cin'];
                    foreach ( $acf_fields as $field ) {
                        if (!isset($_REQUEST[ $field ])) continue;
                        update_field($field, $_REQUEST[ $field ], 'user_'.$customer_id);
                    }
                    break;
            }
            wc_add_notice("Vos données ont étés mise à jour avec succès", "success");
        }
    }, 10);


}, 10);
