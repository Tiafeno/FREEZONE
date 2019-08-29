<?php
namespace classes;

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

class fzGoodDeal
{
    public $ID;
    public $price = 0; // post meta: gd_price
    public $gallery; // post meta: gd_gallery
    public $post_author_annonce = 0; // post meta: gd_author

    public function __construct ($post_id) {
        $post = \WP_Post::get_instance($post_id);
        foreach (get_object_vars($post) as $key => $value)
            $this->$key = $value;

        $this->price  = get_post_meta($post_id, 'gd_price', true);
        $this->gallery = get_post_meta($post_id, 'gd_gallery', true); // return array of ids
        if (!is_array($this->gallery)) {
            $this->gallery = explode(',', $this->gallery);
        }
        $this->post_author_annonce = (int) get_post_meta($post_id, 'gd_author', true);
        $this->categorie = wp_get_post_terms( $this->ID, 'product_cat', [] );

    }

    public function get_author() {
        return new \WP_User($this->post_author_annonce);
    }

    public function get_gallery_thumbnail() {
        $attachment = [];
        if (!is_array($this->gallery)) {
            $this->gallery = explode(',', $this->gallery);
        }

        foreach ($this->gallery as $gallery)
            $attachment[] = wp_get_attachment_url( intval($gallery) );
        return $attachment;
    }

}

add_action('init', function () {
    register_post_type('good-deal', [
        'label' => "Les bonnes affaires",
        'labels' => [
            'name' => "Les bonnes affaires",
            'singular_name' => "Bonne affaire",
            'add_new' => 'Ajouter',
            'add_new_item' => "Ajouter un nouveau",
            'edit_item' => 'Modifier',
            'view_item' => 'Voir',
            'search_items' => "Trouver",
            'all_items' => "Tous les bonnes affaires",
            'not_found' => "Aucun",
            'not_found_in_trash' => "La corbeille est vide"
        ],
        'public' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'show_ui' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'bonne-affaires'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-archive',
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
        'show_in_rest' => true,
        'query_var' => true
    ]);
}, 10);

add_action('rest_api_init', function () {
    $metas = ['gd_price', 'gd_gallery', 'gd_author'];
    foreach ( $metas as $meta ) {
        register_rest_field('good-deal', $meta, [
            'update_callback' => function ($value, $object, $field_name) {
                return update_post_meta((int)$object->ID, $field_name, $value);
            },
            'get_callback' => function ($object, $field_name) {
                return get_post_meta((int)$object['id'], $field_name, true);
            }
        ]);
    }

    //categorie
    register_rest_field('good-deal', 'categorie', [
        'update_callback' => function ($value, $object, $field_name) {
            return wp_set_object_terms( (int)$object->ID, intval($value), 'product_cat', false );
        },
        'get_callback' => function ($object) {
            return wp_get_post_terms( (int)$object['id'], 'product_cat', [] );
        }
    ]);
});