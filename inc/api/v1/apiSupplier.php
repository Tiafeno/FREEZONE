<?php

/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/04/2019
 * Time: 11:27
 */
class apiSupplier
{
    public function __construct ()
    {

    }


    public function collect_suppliers (WP_REST_Request $rq)
    {
        $length = (int)$_POST['length'];
        $start = (int)$_POST['start'];

        $args = [
            'number' => $length,
            'offset' => $start,
            'role' => 'fz-supplier',
        ];

        $the_query = new WP_User_Query($args);
        if (!empty($the_query->get_results())) {

            $suppliers = array_map(function ($supplier) {
                $response = new \classes\fzSupplier($supplier->ID);
                $response->lastname = $response->last_name;
                $response->firstname = $response->first_name;
                return $response;
            }, $the_query->results);

            return [
                "recordsTotal" => (int)$the_query->total_users,
                "recordsFiltered" => (int)$the_query->total_users,
                'data' => $suppliers
            ];
        } else {

            return [
                "recordsTotal" => (int)$the_query->total_users,
                "recordsFiltered" => (int)$the_query->total_users,
                'data' => []
            ];
        }
    }

    public function get_accepted_item_suppliers (WP_REST_Request $request)
    {
        $query_order = new WP_Query([
            'post_type' => wc_get_order_types(),
            'post_status' => 'any',
            "posts_per_page" => -1,
            'meta_query' => [
                [
                    'key' => 'position',
                    'value' => 3, // Demande acceptée
                    'compare' => '='
                ]
            ]
        ]);

        $results = [];
        $fournisseurs = [];
        foreach ( $query_order->posts as $order ) {
            $current_order = new WC_Order($order->ID);
            $items = $current_order->get_items();

            foreach ( $items as $item_id => $item ) {
                $suppliers = wc_get_order_item_meta($item_id, 'suppliers', true);
                $suppliers = json_decode($suppliers);
                $data = $item->get_data();
                if (is_array($suppliers) && !empty($suppliers)) {
                    foreach ( $suppliers as $supplier ) {
                        $user_id = (int)$supplier->supplier;
                        if (!isset($fournisseurs[ $user_id ])) {
                            $fournisseurs[ $user_id ] = [];
                        }

                        $infos = new \stdClass();
                        $infos->quantity = $supplier->get;
                        $infos->price = intval($item->get_total()) / intval($item->get_quantity());
                        $infos->article_id = $supplier->article_id;

                        $fournisseurs[ $user_id ][] = $infos;
                    }
                }
            }
        }

        foreach ( $fournisseurs as $user_id => $infos ) {
            $user_items = [];
            if (is_array($infos)) {
                foreach ( $infos as $info ) {
                    $info_article = new \classes\fzProduct($info->article_id);
                    $info_article->item_quantity = $info->quantity;
                    $info_article->item_price = $info->price;
                    $user_items[] = $info_article;
                }
            }
            $results[] = [
                'user_id' => $user_id,
                'data' => new \classes\fzSupplier($user_id),
                'articles' => $user_items
            ];
        }

        return ($results);


    }

    public function action_collect_suppliers (WP_REST_Request $request)
    {
        global $wpdb;
        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
        $start = isset($_REQUEST['start']) ? (int)$_REQUEST['start'] : 1;
        $action = $request['action'];
        if (empty($action)) wp_send_json_error("Parametre 'action' manquant");
        switch ($action) {
            // Récupérer les fournisseurs en attente de validation
            case 'review':

                // Récuperer tous les demandes en attente
                $orders = new WP_Query([
                    'post_type' => wc_get_order_types(),
                    'post_status' => array_keys(wc_get_order_statuses()),
                    "posts_per_page" => -1,
                    'meta_query' => [
                        [
                            'key' => 'position',
                            'value' => 0, // Demande en attente
                            'compare' => '='
                        ]
                    ]
                ]);

                $product_ids = [];
                foreach ( $orders->posts as $order ) {
                    $current_order = new WC_Order($order->ID);
                    $items = $current_order->get_items();
                    foreach ( $items as $item_id => $item ) {
                        $data = $item->get_data();
                        array_push($product_ids, (int)$data['product_id']);
                    }
                }
                $product_ids = array_unique($product_ids, SORT_NUMERIC);
                $join_product_ids = implode(',', $product_ids);
                if (empty($join_product_ids)) {
                    return [
                        "recordsTotal" => 0,
                        "recordsFiltered" => 0,
                        'data' => []
                    ];
                }

                $today = date_i18n('Y-m-d H:i:s');
                $today_date_time = new DateTime($today);
                $today_date_time->setTime(6, 0, 0); // Ajouter 06h du matin

                $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->users as users
WHERE users.ID IN (
    SELECT CAST(pm2.meta_value AS SIGNED) FROM $wpdb->posts as pts
    JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
    JOIN $wpdb->postmeta as pm2 ON (pm2.post_id = pts.ID)
    JOIN $wpdb->postmeta as pm3 ON (pm3.post_id = pts.ID)
        WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('{$today_date_time->format("Y-m-d H:i:s")}' AS DATETIME)
            AND pm2.meta_key = "user_id"
            AND (pm3.meta_key = "product_id" AND CAST(pm3.meta_value AS SIGNED) IN ($join_product_ids))
            AND pts.post_type = "fz_product" 
            AND pts.post_status = "publish"
    GROUP BY pm.meta_value HAVING COUNT(*) > 0
) 
LIMIT $length OFFSET $start
SQL;
                $results = $wpdb->get_results($sql);
                $count_sql = <<<CPR
SELECT FOUND_ROWS()
CPR;
                $total = $wpdb->get_var($count_sql);
                $Suppliers = [];
                $rest_request = new WP_REST_Request();
                $rest_request->set_param('context', 'edit');
                foreach ( $results as $result ) {
                    $user_controller = new WP_REST_Users_Controller();
                    $data = $user_controller->prepare_item_for_response(new WP_User($result->ID), $rest_request);
                    $Suppliers[] = $data->get_data();
                }

                $wpdb->flush();
                return [
                    "recordsTotal" => $total,
                    "recordsFiltered" => $total,
                    'data' => $Suppliers
                ];

                break;

            default:
                return [
                    "recordsTotal" => 0,
                    "recordsFiltered" => 0,
                    'data' => []
                ];
        }
    }
}

new apiSupplier();