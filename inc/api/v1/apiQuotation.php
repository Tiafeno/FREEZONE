<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/04/2019
 * Time: 14:59
 */

class apiQuotation
{
    public function __construct ()
    {
        add_action('rest_api_init', function () {
            register_rest_route('api', '/typeahead/suppliers', [
                [
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [&$this, 'typeahead_supplier_users'],
                    'permission_callback' => function ($data) {
                        return current_user_can('edit_posts');
                    },
                    'args' => []
                ],
            ]);
        });

    }

    // Recuperer les fournisseurs
    public function typeahead_supplier_users ()
    {
        global $wpdb;
        $search = $_REQUEST['search'];
        $responses = [];
        if (!isset($search) || empty($search)) wp_send_json([]);
        $search_array = explode(' ', $search);
        $search_regex = implode('|', $search_array);
        $sql = "SELECT SQL_CALC_FOUND_ROWS us.ID from {$wpdb->users} as us 
        where 
        us.ID IN (select m.user_id from {$wpdb->usermeta} as m where 
            (m.meta_key = 'first_name' and m.meta_value REGEXP '^({$search_regex})') OR
            (m.meta_key = 'last_name' and m.meta_value REGEXP '^({$search_regex})') OR
            (m.meta_key = 'company_name' and m.meta_value REGEXP '^({$search_regex})') 
            ) LIMIT 5";
        $results = $wpdb->get_results($sql);
        $request = new WP_REST_Request();
        $request->set_param('context', 'edit');
        foreach ( $results as $result ) {
            $usr_controller = new WP_REST_Users_Controller();
            $cs_response = $usr_controller->prepare_item_for_response(new \WP_User((int)$result->ID), $request);
            $data = $cs_response->data;
            if (in_array($data['roles'][0], ['fz-company', 'fz-particular'])) {
                $responses[] = $data;
            }
        }
        $wpdb->flush();
        wp_send_json($responses);
    }

    public function collect_quotations (WP_REST_Request $rq)
    {
        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
        $start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 0;
        $args = [
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
            "posts_per_page" => $length,
            'order' => 'DESC',
            'orderby' => 'ID',
            "offset" => $start
        ];

        /**
         * 0: En attente
         * 1: Envoyer
         * 2: Rejetés
         * 3: Acceptée
         * 4: Terminée
         */
        $args['meta_query'] = [];
        if (isset($_REQUEST['position']) && $_REQUEST['position'] != '') {
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
                $response = new \classes\FZ_Quote($quotation->ID);
                $items = $response->get_items();
                foreach ( $items as $item_id => $item ) {
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

new apiQuotation();