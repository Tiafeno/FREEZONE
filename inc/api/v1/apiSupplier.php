<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 30/04/2019
 * Time: 11:27
 */

class apiSupplier
{
    public function __construct () { }
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
        if ( ! empty($the_query->get_results()) ) {

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

    public function action_collect_suppliers(WP_REST_Request $request) {
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
                    'post_status' => array_keys( wc_get_order_statuses() ),
                    "posts_per_page" => -1,
                    'meta_query' => [
                        [
                            'key' => 'position',
                            'value' => 0,
                            'compare' => '='
                        ]
                    ]
                ]);

                $product_ids = [];
                foreach ($orders->posts as $order) {
                    $current_order = new WC_Order($order->ID);
                    $items = $current_order->get_items();
                    foreach ($items as $item_id => $item) {
                        $data = $item->get_data();
                        $suppliers = wc_get_order_item_meta( $item_id, 'suppliers', true );
                        $suppliers = json_decode($suppliers);
                        if (is_array($suppliers)) {
                            $suppliers = array_filter($suppliers, function ($supplier) { return intval($supplier->get) !== 0; });
                            if (!empty($suppliers)) {
                                array_push($product_ids, (int) $data['product_id']);
                            }
                        }
                    }
                }
                $product_ids = array_unique($product_ids, SORT_NUMERIC );

                $join_product_ids = implode(',', $product_ids);
                $today = date_i18n('Y-m-d H:i:s');
                $review_limit = new DateTime("$today - 2 day");
                $review_limit_string = $review_limit->format('Y-m-d H:i:s');
                $sql = <<<SLQ
SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->users as users
WHERE users.ID IN (
	SELECT CAST(pm2.meta_value AS SIGNED) FROM $wpdb->posts as pts
	JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
    JOIN $wpdb->postmeta as pm2 ON (pm2.post_id = pts.ID)
    JOIN $wpdb->postmeta as pm3 ON (pm3.post_id = pts.ID)
		WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('$review_limit_string' AS DATETIME)
			AND pm2.meta_key = "user_id"
			AND (pm3.meta_key = "product_id" AND CAST(pm3.meta_value AS SIGNED) IN ($join_product_ids))
			AND pts.post_type = "fz_product" 
			AND pts.post_status = "publish"
	GROUP BY pm.meta_value HAVING COUNT(*) > 0
) 
LIMIT $length OFFSET $start
SLQ;
                $results = $wpdb->get_results($sql);
                $count_sql = <<<CPR
SELECT FOUND_ROWS()
CPR;
                $total = $wpdb->get_var($count_sql);
                $Suppliers = [];
                foreach ($results as $result) {
                    $Suppliers[] = new \classes\fzSupplier((int) $result->ID);
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