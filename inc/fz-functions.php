<?php
require_once 'model/fz-model.php';
require_once 'shortcodes/after-sales-service.php';
require_once 'classes/fzRoles.php';
require_once 'classes/fzPTFreezone.php';

if (!defined('TWIG_TEMPLATE_PATH')) {
    define('TWIG_TEMPLATE_PATH', get_stylesheet_directory() . '/templates');
}
try {
    $file_system = new Twig_Loader_Filesystem();
    $file_system->addPath(TWIG_TEMPLATE_PATH . '/vc', 'VC');
    $file_system->addPath(TWIG_TEMPLATE_PATH . '/shortcodes', 'SC');
    /** @var Object $Engine */
    $Engine = new Twig_Environment($file_system, [
        'debug' => false,
        'cache' => TWIG_TEMPLATE_PATH . '/cache',
        'auto_reload' => true
    ]);

} catch (Twig_Error_Loader $e) {
    return new WP_Error('broke', $e->getRawMessage());
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
    if (is_null(get_role('fz-supplier')) || is_null(get_role('fz-particular'))) {
        fzRoles::create_roles();
        // Delete old role
        if (get_role('particular')) remove_role('particular');
        if (get_role('supplier')) remove_role('supplier');
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

}, 100);

add_action('init', function () {
    // Init wordpress
});