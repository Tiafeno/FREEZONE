<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 14/05/2019
 * Time: 02:25
 */

class apiArticle
{
    public function __construct () {}

    public function action_collect_articles(WP_REST_Request $request) {
        global $wpdb;

        $action = $request['action'];
        if (empty($action)) wp_send_json_error("Parametre 'action' manquant");
        $length = isset($_REQUEST['length']) ? (int)$_REQUEST['length'] : 10;
        $start = isset($_REQUEST['length']) ? (int)$_REQUEST['start'] : 1;
        switch ($action) {
            case 'review':

                $today = date_i18n('Y-m-d H:i:s');
                $review_limit = new DateTime("$today - 2 day");
                $review_limit_string = $review_limit->format('Y-m-d H:i:s');
                $sql = <<<SLQ
  SELECT SQL_CALC_FOUND_ROWS pm.* FROM $wpdb->posts as pts
	JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
		WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('$review_limit_string' AS DATETIME)
			AND pts.post_type = "fz_product"
	GROUP BY pm.meta_value HAVING COUNT(*) > 0
    LIMIT $length OFFSET $start
SLQ;
                $results = $wpdb->get_results($sql);
                $count_sql = <<<CPR
SELECT FOUND_ROWS()
CPR;
                $total = $wpdb->get_var($count_sql);
                $Suppliers = [];
                foreach ($results as $result) {
                    $Suppliers[] = new \classes\fzSupplierArticle((int) $result->post_id, 'edit');
                }

                return [
                    "recordsTotal" => intval($total),
                    "recordsFiltered" => intval($total),
                    'data' => $Suppliers
                ];

                break;

            case 'review_articles':
                $supplier_id = $request['supplierid'];
                $supplier_id = intval($supplier_id);
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

                $articles = [];
                foreach ($orders->posts as $order) {
                    $current_order = new WC_Order($order->ID);
                    $items = $current_order->get_items();
                    foreach ($items as $item_id => $item) {
                        $suppliers = wc_get_order_item_meta( $item_id, 'suppliers', true );
                        $suppliers = json_decode($suppliers);
                        if (is_array($suppliers)) {
                            $suppliers = array_filter($suppliers, function ($supplier) { return intval($supplier->get) !== 0; });
                            foreach ($suppliers as $supplier) {
                                if ($supplier->supplier !== $supplier_id) continue;
                                array_push($articles, new \classes\fzSupplierArticle( (int) $supplier->article_id));
                            }
                        }
                    }
                }

                return [
                    "recordsTotal" => count($articles),
                    "recordsFiltered" => count($articles),
                    'data' => $articles
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
    public function action_collect_supplier_articles() {

    }
}