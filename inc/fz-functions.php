<?php
// Désactiver l'access à la back-office pour les utilisateurs non admin
add_action('after_setup_theme', function () {
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
});

add_action('admin_init', function () {
    if (is_null(get_role('particular')) || is_null(get_role('supplier'))) {
        create_roles();
    }

    if (is_user_logged_in()) {
        $User = wp_get_current_user();
        $roles = $User->roles;
        $isRole = in_array( 'particular', $roles ) || in_array( 'supplier', $roles );
        $redirect = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : home_url( '/' );
        if ( is_admin() && ! defined( 'DOING_AJAX' ) && $isRole ) {
            exit( wp_redirect( $redirect, 301 ) );
        }
    }

}, 100);
add_action('init', "constructor");
function constructor ()
{
    // Init action

}

function create_roles ()
{
    $capabilities = array(
        'read'                   => true,  // true allows this capability
        'upload_files'           => true,
        'edit_others_pages'      => true,
        'edit_others_posts'      => true,
        'edit_pages'             => false,
        'edit_posts'             => true,
        'edit_users'             => true,
        'manage_options'         => false,
        'remove_users'           => false,
        'delete_others_pages'    => true,
        'delete_posts'           => false,
        'delete_pages'           => false,
        'delete_published_posts' => false,
        'delete_users'           => false,
        'delete_themes'          => false,
        'delete_plugins'         => false,
        'create_users'           => false,
        'create_posts'           => true, // Allows user to create new posts
        'manage_categories'      => true, // Allows user to manage post categories
        'publish_posts'          => true, // Allows the user to publish, otherwise posts stays in draft mode
        'edit_themes'            => false, // false denies this capability. User can’t edit your theme
        'install_plugins'        => false, // User cant add new plugins
        'update_plugin'          => false, // User can’t update any plugins
        'update_core'            => false, // user cant perform core updates
        'create_users'           => false,
        'install_themes'         => false,
    );

    $roles = [
        ['role' => 'particular', 'display_name' => "Particulier"],
        ['role' => 'supplier', 'display_name' => "Fournisseur"],
    ];

    foreach ($roles as $role):
        add_role($role['role'], $role['display_name'], $capabilities);
    endforeach;
}