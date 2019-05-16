<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 07/05/2019
 * Time: 00:01
 */

class apiProduct
{
    public function __construct () { }

    public function collect_products (WP_REST_Request $rq)
    {
        $options = get_field('wc', 'option');
//        $woocommerce = new \Automattic\WooCommerce\Client(
//            "http://{$_SERVER['SERVER_NAME']}",
//            $options['ck'],
//            $options['cs'],
//            [
//                'version' => 'wc/v3'
//            ]
//        );
        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];
        $args = [
            'limit' => $length,
            'offset' => $start,
            'paginate' => true,
            'post_type' => 'product'
        ];

        $the_query = new WP_Query($args);
        $products = array_map(function ($product) {
            // $result = $woocommerce->get("products/{$product->ID}", ['context' => 'view']);
            $product = wc_get_product($product->ID);
            $pdt = new stdClass();
            $pdt->ID = $product->get_id();
            $pdt->name = $product->get_name();
            $pdt->sku = $product->get_sku();
            $pdt->categories = $product->get_categories();
            $pdt->status = $product->get_status();
            $pdt->date_created = $product->get_date_created();
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
}