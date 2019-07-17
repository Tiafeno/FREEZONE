<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/04/2019
 * Time: 14:59
 */

class apiQuotation
{
    public function __construct () { }

    public function collect_quotations (WP_REST_Request $rq)
    {
        $length = (int)$_POST['length'];
        $start = (int)$_POST['start'];
        $args = [
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys( wc_get_order_statuses() ),
            "posts_per_page" => $length,
            'order' => 'DESC',
            'orderby' => 'ID',
            "offset" => $start
        ];

        if ( isset($_POST['position']) && $_POST['position'] != '' ) {
            if (is_array($_POST['position'])) {
                $position = $_POST['position'];
                $meta_query = ['relation' => 'OR'];
                $meta = array_map(function ($key) {
                    return [
                        'key' => 'position',
                        'value' => $key,
                        'compare' => '='
                    ];
                }, array_values($position));

                $meta_query = array_merge($meta_query, $meta);
                $args['meta_query'] = $meta_query;
            } else {
                $position = intval($_POST['position']);
                if (!is_nan($position)) {
                    $args['meta_query'] = [
                        [
                            'key' => "position",
                            'value' => $position,
                            'compare' => "="
                        ]
                    ];
                }
            }

        }

        $the_query = new WP_Query($args);
        if ($the_query) {
            $quotations = array_map(function ($quotation) {
                $response = new \classes\fzQuotation($quotation->ID);
                $response->fzItems = $response->get_items();
                $response->author = $response->get_author();
                return $response;
            }, $the_query->posts);

            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                'data' => $quotations
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