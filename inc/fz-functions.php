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
require_once 'classes/fzSupplierArticle.php';
require_once 'classes/fzQuotation.php';
require_once 'classes/fzQuotationProduct.php';
require_once 'classes/fzGoodDeal.php';
require_once 'classes/fzCarousel.php';
require_once 'classes/fzCatalogue.php';

require_once 'api/v1/apiGoodDeal.php';
require_once 'api/v1/apiQuotation.php';
require_once 'api/v1/apiSupplier.php';
require_once 'api/v1/apiProduct.php';
require_once 'api/v1/apiFzProduct.php';
require_once 'api/v1/apiArticle.php';
require_once 'api/v1/apiMail.php';
require_once 'api/v1/apiSav.php';
require_once 'api/v1/apiImport.php';
require_once 'api/fzAPI.php';

require_once 'cron/task-cron.php';

if (!defined('TWIG_TEMPLATE_PATH')) {
    define('TWIG_TEMPLATE_PATH', get_stylesheet_directory() . '/templates');
}

if (!defined('__SITENAME__')) {
    define('__SITENAME__', 'freezone');
}

if (function_exists('acf_add_options_page')) {
    $parent = acf_add_options_page(array(
        'page_title' => 'Parametre Freezone',
        'menu_title' => 'Parametre Freezone',
        'capability' => 'delete_users',
        'autoload' => true,
        'redirect' => false
    ));
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

    // Crée des filtres pour les template TWIG
    $Engine->addFilter(new Twig_SimpleFilter('fakediscount', function ($item) {
        $has_discount = wc_get_order_item_meta($item->get_id(), 'has_discount', true);
        $fake_discount = wc_get_order_item_meta( $item->get_id(), 'fake_discount', true );
        $has_discount= $has_discount ? boolval(intval($has_discount)) : true;

        return $has_discount ? $fake_discount : '';
    }));

} catch (Twig_Error_Loader $e) {
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
        $columns['marge'] = '%';
        $columns['marge_dealer'] = '% R.';
        $columns['marge_particular'] = '% P.';

        return $columns;
    });

    // Afficher les valeurs par colonne
    add_action('manage_fz_product_posts_custom_column', function ($column, $post_id) {
        $p = get_post($post_id);
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
            $marge_dealer = get_post_meta($post_id, 'marge_particular', true);
            $marge_dealer = $marge_dealer ? $marge_dealer : 0;
            echo "{$marge_dealer} %";
        endif;
    }, 10, 2);

}, 100);

add_action('init', function () {
    function search_products() {
        $search_results = new WP_Query( array(
            's' => esc_sql($_REQUEST['q']),
            'post_type' => 'product',
            'post_status' => 'publish',
            //'ignore_sticky_posts' => 1,
            'posts_per_page' => 20
        ) );
        wp_send_json_success($search_results->posts);
    }
    add_action('wp_ajax_searchproducts', 'search_products');
    add_action('wp_ajax_nopriv_searchproducts', 'search_products');

    // Init wordpress
    /**
     * Register the 'Custom Column' column in the importer.
     *
     * @param array $options
     * @return array $options
     */
    function add_column_to_importer( $options ) {
        $options['attribute'] = 'Attribut';
        $options['attribute_value'] = 'Attribut valeur';

        return $options;
    }
    add_filter( 'woocommerce_csv_product_import_mapping_options', 'add_column_to_importer' );

    /**
     * Add automatic mapping support for 'Custom Column'.
     * This will automatically select the correct mapping for columns named 'Custom Column' or 'custom column'.
     *
     * @param array $columns
     * @return array $columns
     */
    function add_column_to_mapping_screen( $columns ) {

        // potential column name => column slug
        $columns['Attribut'] = 'attribute';
        $columns['Attribut valeur'] = 'attribute_value';

        return $columns;
    }
    add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'add_column_to_mapping_screen' );

    /**
     * Process the data read from the CSV file.
     * This just saves the value in meta data, but you can do anything you want here with the data.
     *
     * @param WC_Product $object - Product being imported or updated.
     * @param array $data - CSV data read for the product.
     * @return WC_Product $object
     */
    function process_import( $object, $data ) {
        $attributes = $data['attribute'];
        $attribute_values = $data['attribute_value'];

        if (empty($attributes)) return $object;

        $attributes = explode(',', $attributes);
        $attribute_values = explode(',', $attribute_values);

        foreach ($attributes as $key => $attr) {
            $attrs = [];
            if (empty($attr)) continue;

            // Get attribute by identification
            $attr_id = wc_attribute_taxonomy_id_by_name( sanitize_title($attr) ); // @return int
            if (0 === $attr_id) {
                $args = [
                    'name'  => ucfirst( stripslashes($attr) ),
                    'has_archives'  => true
                ];
                $response = wc_create_attribute($args); // return int|WP_Error
                if (is_wp_error($response)) continue;
                $attr_id = $response;
            }

            $objet_attribute = wc_get_attribute($attr_id); // return stdClass(id, slug, name ...) otherwise null
            if (is_null($objet_attribute)) continue;
            $attrs[] = ucfirst($attribute_values[$key]); // set attribute value

            wp_set_object_terms( $object->get_id(), $attrs, $objet_attribute->slug );
        }

        //update_post_meta($object->get_id(), '_product_attributes', $attrs);
        
        return $object;
    }
    add_filter( 'woocommerce_product_import_pre_insert_product_object', 'process_import', 10, 2 );

    add_action('wp_enqueue_scripts', function () {
        wp_register_style( 'owlCarousel', get_stylesheet_directory_uri() . '/assets/js/owlcarousel/assets/owl.carousel.min.css', '', '2.0.0' );
        wp_register_style( 'owlCarousel-green', get_stylesheet_directory_uri() . '/assets/js/owlcarousel/assets/owl.theme.green.min.css', '', '2.0.0' );
        wp_register_script( 'owlCarousel', get_stylesheet_directory_uri() . '/assets/js/owlcarousel/owl.carousel.min.js', ['jquery'], '2.0.0', true );
    });


}, 10);
