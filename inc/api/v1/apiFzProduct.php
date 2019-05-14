<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 12/05/2019
 * Time: 13:54
 */

class apiFzProduct
{
    public function __construct () {}
}

add_action('rest_api_init', function () {
    add_filter('rest_fz_product_query', function($args, $request) {
        $args += array(
            'meta_key'   => $request['meta_key'],
            'meta_value' => $request['meta_value'],
            'meta_query' => $request['meta_query'],
        );
        return $args;
    }, 99, 2);
});