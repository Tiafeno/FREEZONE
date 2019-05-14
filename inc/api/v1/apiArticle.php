<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 14/05/2019
 * Time: 02:25
 */

class apiArticle
{
    public function __construct () { }
    public function action_collect_articles(WP_REST_Request $request) {
        global $wpdb;

        $length = (int)$_REQUEST['length'];
        $start = (int)$_REQUEST['start'];

        $action = $request['action'];
        if (empty($action)) wp_send_json_error("Parametre 'action' manquant");
        switch ($action) {
            case 'review':
                $today = date_i18n('Y-m-d H:i:s');
                $review_limit = new DateTime("$today - 2 day");
                $review_limit_string = $review_limit->format('Y-m-d H:i:s');
                $sql = <<<SLQ
  SELECT pm.* FROM $wpdb->posts as pts
	JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
		WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('$review_limit_string' AS DATETIME)
			AND pts.post_type = "fz_product" 
			AND pts.post_status = "publish"
	GROUP BY pm.meta_value HAVING COUNT(*) > 0
    LIMIT $length OFFSET $start
SLQ;
                $results = $wpdb->get_results($sql);
                $Suppliers = [];
                foreach ($results as $result) {
                    $Suppliers[] = new \classes\fzSupplierArticle((int) $result->post_id, 'edit');
                }

                $count_sql = <<<CPR
SELECT COUNT(*) FROM $wpdb->posts as pts
	JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
		WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('$review_limit_string' AS DATETIME)
			AND pts.post_type = "fz_product" 
			AND pts.post_status = "publish"
	GROUP BY pm.meta_value HAVING COUNT(*) > 0
CPR;
                $total = $wpdb->get_var($count_sql);
                return [
                    "recordsTotal" => intval($total),
                    "recordsFiltered" => intval($total),
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