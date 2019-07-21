<?php

namespace classes;

/**
 * Class fzPTFreezone - Custom Freezone post type
 */
class fzPTFreezone
{
    public function __construct () {
        add_action('init', function () {
           $this->register_post_type();
        });

        add_action('init', function () {
            register_taxonomy_for_object_type('product_cat', 'fz_product');
            register_taxonomy_for_object_type('product_cat', 'good-deal');
        }, 11);

        add_action('init', function () {
            // Afficher la taxonomie dans le rest api
            $product_cat = get_taxonomy('product_cat');
            $product_cat->show_in_rest = true;
        }, 30);

        add_action('admin_init', function () {
            $caps = [
              ['read_article' => ['administrator', 'fz-supplier', 'fz-particular', 'fz-company', 'editor', 'author']],
              ['read_private_article' => ['administrator', 'editor', 'author']],
              ['edit_article' => ['administrator', 'fz-supplier', 'editor', 'author']],
              ['edit_articles' => ['administrator', 'fz-supplier', 'editor', 'author']],
              ['edit_others_articles' => ['administrator', 'editor', 'author']],
              ['edit_published_articles' => ['administrator', 'fz-supplier', 'editor', 'author']],
              ['edit_private_articles' => ['administrator', 'editor', 'author']],
              ['delete_article'  => ['administrator', 'fz-supplier']],
              ['delete_articles' => ['administrator', 'fz-supplier']],
              ['delete_others_articles' => ['administrator']],
              ['delete_published_articles' => ['administrator', 'fz-supplier']],
              ['delete_private_articles' => ['administrator']],
              ['publish_articles' => ['administrator', 'fz-supplier', 'editor']],
            ];
            $caps = array_merge($caps, [
                ['read_sav' => ['administrator', 'fz-supplier', 'fz-particular', 'fz-company', 'editor', 'author']],
                ['read_private_sav' => ['administrator']],
                ['edit_sav' => ['administrator', 'fz-supplier', 'fz-particular', 'fz-company', 'editor', 'author']],
                ['edit_savs' => ['administrator', 'fz-supplier', 'fz-particular', 'fz-company', 'editor', 'author']],
                ['edit_others_savs' => ['administrator', 'fz-particular', 'fz-company', 'fz-supplier']],
                ['edit_published_savs' => ['administrator', 'fz-supplier', 'fz-particular', 'fz-company']],
                ['edit_private_savs' => ['administrator']],
                ['delete_sav'  => ['administrator']],
                ['delete_savs' => ['administrator']],
                ['delete_others_savs' => ['administrator']],
                ['delete_published_savs' => ['administrator']],
                ['delete_private_savs' => ['administrator']],
                ['publish_savs' => ['administrator', 'fz-supplier', 'fz-particular', 'fz-company']],
            ]);
            foreach ($caps as $cap):
                if (is_array($cap)) {
                    foreach ($cap as $capabilitie => $roles) {
                        if (is_array($roles)) {
                            foreach ($roles as $role):
                                $user_role = get_role($role);
                                if (is_null($user_role)) continue;
                                if ( ! $user_role->has_cap($capabilitie) ):
                                    $user_role->add_cap($capabilitie);
                                endif;

                                endforeach;
                        }
                    }
                }
                endforeach;

            $editor_role = get_role('editor');
            $caps = ['list_users', 'edit_users'];
            foreach ($caps as $cap) {
                if ( ! $editor_role->has_cap($cap) ) {
                    $editor_role->add_cap($cap);
                }
            }


        });
    }

    protected function register_post_type ()
    {
        register_post_type('fz_product', [
            'label' => "Les articles",
            'labels' => [
                'name' => "Les articles",
                'singular_name' => "Article",
                'add_new' => 'Ajouter',
                'add_new_item' => "Ajouter un nouveau article",
                'edit_item' => 'Modifier',
                'view_item' => 'Voir',
                'search_items' => "Trouver des articles",
                'all_items' => "Tous les articles",
                'not_found' => "Aucun article trouver",
                'not_found_in_trash' => "Aucun article dans la corbeille"
            ],
            'public' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'show_ui' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'article'],
            'capabilities' => [
                'read_post' => 'read_article',
                'read_private_posts' => 'read_private_article',
                'edit_post' => 'edit_article',
                'edit_posts' => 'edit_articles',
                'edit_others_posts' => 'edit_others_articles',
                'edit_published_posts' => 'edit_published_articles',
                'edit_private_posts' => 'edit_private_articles',
                'delete_post' => 'delete_article',
                'delete_posts' => 'delete_articles',
                'delete_others_posts' => 'delete_others_articles',
                'delete_published_posts' => 'delete_published_articles',
                'delete_private_posts' => 'delete_private_articles',
                'publish_posts' => 'publish_articles',
                //'moderate_comments'			=> 'moderate_formation_comments',
            ],
            //'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-archive',
            'menu_position' => 100,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true,
            'query_var' => true
        ]);


        register_post_type('fz_sav', [
            'label' => "S.A.V",
            'labels' => [
                'name' => "S.A.V",
                'singular_name' => "Service après vente",
                'add_new' => 'Ajouter',
                'add_new_item' => "Ajouter un S.A.V",
                'edit_item' => 'Modifier',
                'view_item' => 'Voir',
                'search_items' => "Trouver des S.A.V",
                'all_items' => "Tous les S.A.V",
                'not_found' => "Aucun service trouver",
                'not_found_in_trash' => "Aucun service dans la corbeille"
            ],
            'public' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'show_ui' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'sav'],
            'capabilities' => [
                'read_post' => 'read_sav',
                'read_private_posts' => 'read_private_sav',
                'edit_post' => 'edit_sav',
                'edit_posts' => 'edit_savs',
                'edit_others_posts' => 'edit_others_savs',
                'edit_published_posts' => 'edit_published_savs',
                'edit_private_posts' => 'edit_private_savs',
                'delete_post' => 'delete_sav',
                'delete_posts' => 'delete_savs',
                'delete_others_posts' => 'delete_others_savs',
                'delete_published_posts' => 'delete_published_savs',
                'delete_private_posts' => 'delete_private_savs',
                'publish_posts' => 'publish_savs',
                //'moderate_comments'			=> 'moderate_formation_comments',
            ],
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-archive',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true,
            'query_var' => true
        ]);


    }
}

new fzPTFreezone();