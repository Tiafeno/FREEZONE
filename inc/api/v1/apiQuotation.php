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
        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
        $start  = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
        $args = [
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys( wc_get_order_statuses() ),
            "posts_per_page" => $length,
            'order'   => 'DESC',
            'orderby' => 'ID',
            "offset"  => $start
        ];

        /**
         * 0: En attente
         * 1: Envoyer
         * 2: Rejetés
         * 3: Acceptée
         * 4: Terminée
         */
        $args['meta_query'] = [];
        if ( isset($_REQUEST['position']) && $_REQUEST['position'] != '' ) {
            if (is_array($_REQUEST['position'])) {
                $position = $_REQUEST['position'];
                $meta_query = ['relation' => 'OR'];
                $meta = array_map(function ($key) {
                    return [
                        'key' => 'position',
                        'value' => $key,
                        'compare' => '='
                    ];
                }, array_values($position));

                $meta_query = array_merge($meta_query, $meta);
                $args['meta_query'][] = $meta_query;
            } else {
                $position = intval($_REQUEST['position']);
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

        if (isset($_REQUEST['role']) && $_REQUEST['role'] !== '') {
            $role = $_REQUEST['role'];
            $args['meta_query']['relation'] = 'AND';
            array_push($args['meta_query'], [
                'key' => 'client_role',
                'value' => $role,
                'compare' => "="
            ]);
        }

        $the_query = new WP_Query($args);
        if ($the_query) {
            $quotations = array_map(function ($quotation) {
                $response = new \classes\fzQuotation($quotation->ID);
                $items = $response->get_items();
                foreach ($items as $item_id => $item) {
                    $data = $item->get_data();
                    $response->fzItems[] = $data;
                }

                $rest_request = new WP_REST_Request();
                $rest_request->set_param('context', 'edit');
                
                $user_controller = new WP_REST_Users_Controller();
                $data = $user_controller->prepare_item_for_response(new WP_User((int)$response->user_id), $rest_request);
                $response->author = $data->get_data();
                return $response;
            }, $the_query->posts);


            return [
                "recordsTotal" => (int)$the_query->found_posts,
                "recordsFiltered" => (int)$the_query->found_posts,
                "args" => $args,
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