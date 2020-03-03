<?php

add_action('api_get_good_deal', function (WP_REST_Request $rq) {

});

class apiGoodDeal {
    public function __construct() {
        add_action('init', function() {
             // Ajouter des champs dans rest response pour l'API
            register_meta('post', "gd_price", [
                'object_subtype' => 'good-deal',
                'type' => 'number',
                'single' => true,
                'show_in_rest' => true,
            ]);
            register_meta('post', "gd_gallery", [
                'object_subtype' => 'good-deal',
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
            ]);
            register_meta('post', "gd_author", [
                'object_subtype' => 'good-deal',
                'type' => 'number',
                'single' => true,
                'show_in_rest' => true,
            ]);
        });
        add_action('rest_api_init', function (){
            register_rest_route('api', '/good-deals/', [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [&$this, 'collect_good_deal_posts'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ]
            ]);

            // Ajouter une filtre pour les metas donnees
            add_filter('rest_good-deal_query', function($args, $request) {
                $args += array(
                    'meta_key'   => $request['meta_key'],
                    'meta_value' => $request['meta_value'],
                    'meta_query' => $request['meta_query'],
                );
                return $args;
            }, 99, 2);

            //categorie pour le post
            register_rest_field('good-deal', 'categorie', [
                'update_callback' => function ($value, $object, $field_name) {
                    return wp_set_object_terms( (int)$object->ID, intval($value), 'product_cat', false );
                },
                'get_callback' => function ($object) {
                    return wp_get_post_terms( (int)$object['id'], 'product_cat', [] );
                }
            ]);
        });
    }

    public function collect_good_deal_posts(WP_REST_Request $request) {
        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];
        $args = [
            'limit' => $length,
            'offset' => $start,
            'paginate' => true,
            'post_type' => 'good-deal'
        ];

        $the_query = new WP_Query($args);
        $gdeals = array_map(function ($gdeal) {
            return new \classes\fzGoodDeal($gdeal->ID, true);;
        }, $the_query->posts);

        if ($the_query->have_posts()) {
            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => $gdeals
            ];
        } else {

            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => []
            ];
        }
    }
}

new apiGoodDeal();

?>