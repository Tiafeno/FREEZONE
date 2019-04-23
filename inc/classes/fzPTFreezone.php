<?php

namespace classes;

/**
 * Class fzPTFreezone - Custom Freezone post type
 */
class fzPTFreezone
{
    public function __construct () {
        add_action('init', function () {
           $this->create_posttypes();
        });

        add_action('admin_init', function () {
            $caps = [
              ['read_article' => ['administrator', 'fz-supplier', 'fz-particular']],
              ['read_private_article' => ['administrator']],
              ['edit_article' => ['administrator', 'fz-supplier']],
              ['edit_articles' => ['administrator', 'fz-supplier']],
              ['edit_others_articles' => ['administrator']],
              ['edit_published_articles' => ['administrator', 'fz-supplier']],
              ['edit_private_articles' => ['administrator']],
              ['delete_article' => ['administrator', 'fz-supplier']],
              ['delete_articles' => ['administrator', 'fz-supplier']],
              ['delete_others_articles' => ['administrator']],
              ['delete_published_articles' => ['administrator', 'fz-supplier']],
              ['delete_private_articles' => ['administrator']],
              ['publish_articles' => ['administrator', 'fz-supplier']],
            ];

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
        });
    }

    protected function create_posttypes ()
    {
        register_post_type('fz-article', [
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
            'has_archive' => true,
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
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
            'show_in_rest' => true
        ]);
    }
}

new fzPTFreezone();