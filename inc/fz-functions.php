<?php
require_once 'model/fz-model.php';

require_once "lib/underscore.php";

require_once 'shortcodes/after-sales-service.php';

require_once 'classes/fzRoles.php';
require_once 'classes/fzSav.php';
require_once 'classes/fzPTFreezone.php';
require_once 'classes/fzParticular.php';
require_once 'classes/fzSupplier.php';
require_once 'classes/fzSupplierArticle.php';
require_once 'classes/fzQuotation.php';
require_once 'classes/fzQuotationProduct.php';
require_once 'classes/fzGoodDeal.php';

require_once 'api/v1/apiQuotation.php';
require_once 'api/v1/apiSupplier.php';
require_once 'api/v1/apiProduct.php';
require_once 'api/v1/apiFzProduct.php';
require_once 'api/v1/apiArticle.php';
require_once 'api/v1/apiMail.php';
require_once 'api/v1/apiSav.php';
require_once 'api/fzAPI.php';

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
        'auto_reload' => true
    ]);

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

add_action('wp_loaded', function () {

    // Wordpress loaded
});

add_action('admin_init', function () {
    if (is_null(get_role('fz-supplier')) || is_null(get_role('fz-particular'))) {
        \classes\fzRoles::create_roles();
    }

    if (is_user_logged_in()) {
        $User = wp_get_current_user();
        $roles = $User->roles;
        $isRole = in_array('fz-particular', $roles) || in_array('fz-supplier', $roles);
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url('/');
        if (is_admin() && !defined('DOING_AJAX') && $isRole) {
            exit(wp_redirect($redirect, 301));
        }
    }

    // Afficher les marges
    add_filter('manage_product_posts_columns', function ($columns) {
        $columns['marge'] = '%';

        return $columns;
    });

    add_action('manage_product_posts_custom_column', function ($column, $post_id) {
        if ($column === 'marge'):
            $p = wc_get_product($post_id);
            $marge = $p->get_meta('_fz_marge', true);
            $marge = $marge ? $marge : 0;
            echo "{$marge} %";
            endif;
    }, 10, 2);

}, 100);

add_action('init', function () {
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

        // column slug => column name
        $options['_fz_marge'] = 'Marge du produit';

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
        $columns['Marge du produit'] = '_fz_marge';

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

        if ( ! empty( $data['_fz_marge'] ) ) {
            $object->update_meta_data( '_fz_marge', $data['_fz_marge'] );
        }

        return $object;
    }
    add_filter( 'woocommerce_product_import_pre_insert_product_object', 'process_import', 10, 2 );


}, 10);

function search_products() {
    $search_results = new WP_Query( array(
        's' => esc_sql($_REQUEST['q']), // the search query
        'post_type' => 'product',
        'post_status' => 'publish',
        //'ignore_sticky_posts' => 1,
        'posts_per_page' => 20
    ) );

    wp_send_json_success($search_results->posts);
}