<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 07/05/2019
 * Time: 00:01
 */

class apiProduct
{
    public function __construct () {
        add_action('rest_api_init', function () {
             register_rest_route('api', '/product/', [
                [
                    'methods' => \WP_REST_Server::CREATABLE,
                    'callback' => [&$this, 'handler_post_products'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    }
                ],
            ]);
            register_rest_route('api', '/products/categories', [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [&$this, 'handler_taxonomie_categories'],
                ],
            ]);
        });
    }
    public function handler_post_products (WP_REST_Request $rq) {
        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];
        $search = empty($_REQUEST['search']) ? '' : esc_sql( $_REQUEST['search'] );
        $args = [
            'limit' => $length,
            'offset' => $start,
            'paginate' => true,
            'post_type' => 'product'
        ];

        if (!empty($search)) {
            $args += ['s' => $search];
        }

        $the_query = new WP_Query($args);
        $products = array_map(function ($product) {
            $product = wc_get_product($product->ID);
            $pdt = new stdClass();
            $pdt->ID = $product->get_id();
            $pdt->name = $product->get_name();
            $pdt->sku = $product->get_sku();
            $pdt->categories = $product->get_categories();
            $pdt->status = $product->get_status();
            $pdt->date_created = $product->get_date_created();
            $pdt->marge = (int) $product->get_meta('_fz_marge', true);
            return $pdt;
        }, $the_query->posts);

        if ($the_query->have_posts()) {
            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => $products
            ];
        } else {

            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => []
            ];
        }

    }

    public function handler_taxonomie_categories(WP_REST_Request $rq) {
        $number = isset($_GET['number']) ? intval(sanitize_text_field($_GET['number'])) : 0;
        $taxonomies = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'number' => $number
        ) );
        wp_send_json($taxonomies);
    }
}

new apiProduct();