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
            unset($post);
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

    register_rest_route('api', '/catalog/', [
        [
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => function (\WP_REST_Request $rq) {
                $per_page = isset($_REQUEST['per_page']) ? (int)$_REQUEST['per_page'] : 20;
                $offset  = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
                $args = [
                    'post_type' => 'catalog',
                    'post_status' => 'publish',
                    'number' => $per_page,
                    'offset' => $offset,
                ];

                $query = new \WP_Query($args);
                if ($query->have_posts()) {
                    $results = [];
                    $request = new \WP_REST_Request();
                    $request->set_param('context', 'edit');

                    while ( $query->have_posts() ) {
                        $query->next_post();
                        $article_controller = new \WP_REST_Posts_Controller('catalog');
                        $post = get_post((int) $query->post->ID);
                        $response = $article_controller->prepare_item_for_response($post, new \WP_REST_Request());
                        $results[] = $response->get_data();
                    }
                    return [
                        "recordsTotal" => (int) $query->found_posts,
                        "recordsFiltered" => (int) $query->found_posts,
                        'data' => $results
                    ];
                } else {
                    return [
                        "recordsTotal" => 0,
                        "recordsFiltered" => 0,
                        'data' => []
                    ];
                }
            },
            'permission_callback' => function ($data) {
                return current_user_can('edit_posts');
            }
        ],
    ]);

});
add_action('wp_ajax_send_selected_ctg', function () {

});