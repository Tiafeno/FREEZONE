<?php
namespace classes;

class fzRoles
{
    public function __construct ()
    {
    }

    public static function create_roles ()
    {
        $capabilities = [
            'read' => true,  // true allows this capability
            'upload_files' => false,
            'edit_others_pages' => true,
            'edit_others_posts' => true,
            'edit_pages' => true,
            'edit_posts' => true,
            'edit_users' => true,
            'manage_options' => false,
            'remove_users' => false,
            'delete_others_pages' => true,
            'delete_posts' => true,
            'delete_pages' => false,
            'delete_published_posts' => true,
            'delete_users' => false,
            'delete_themes' => false,
            'delete_plugins' => false,
            'create_posts' => true, // Allows user to create new posts
            'manage_categories' => true, // Allows user to manage post categories
            'publish_posts' => true, // Allows the user to publish, otherwise posts stays in draft mode
            'edit_themes' => false, // false denies this capability. User can’t edit your theme
            'install_plugins' => false, // User cant add new plugins
            'update_plugin' => false, // User can’t update any plugins
            'update_core' => false, // user cant perform core updates
            'create_users' => false,
            'install_themes' => false,
        ];

        add_role('fz-supplier', 'Fournisseur', $capabilities);
        add_role('fz-particular', 'Particulier', $capabilities);
        add_role('fz-company', 'Entreprise', $capabilities);
    }
}