<?php
namespace classes;


if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

final class fzCatalogue {
    public $ID;
    public $name;
    public $fields = [ // meta field
        'ctg_platform',
        'ctg_observation',
        'ctg_price'
    ];
    public function __construc($post_id) {
        if (\is_numeric($post_id)) {
            $post = get_post(intval($post_id));
            $this->ID = $post->ID;
            $this->name = $post->post_title;

            foreach ($this->fields as $field) {
                $this->$field = get_post_meta( $this->ID, $field, true );
            }
        } else {
            return new \WP_Error('', "Parametre manquant (post_id)");
        }
    }

}

add_action('init', function () {
    register_post_type('catalog', [
        'label' => "Les catalogues",
        'public' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'show_ui' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'catalog'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'menu_icon' => 'dashicons-media-spreadsheet',
        'supports' => ['title', 'excerpt', 'custom-fields'],
        'show_in_rest' => true,
        'query_var' => true
    ]);
}, 10);

add_action('rest_api_init', function () {
    $metas = ['ctg_platform', 'ctg_observation', 'ctg_price'];
    foreach ( $metas as $meta ) {
        register_rest_field('catalog', $meta, [
            'update_callback' => function ($value, $object, $field_name) {
                return update_post_meta((int)$object->ID, $field_name, $value);
            },
            'get_callback' => function ($object, $field_name) {
                return get_post_meta((int)$object['id'], $field_name, true);
            }
        ]);
    }

});