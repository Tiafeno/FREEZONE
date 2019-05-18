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
SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->users as users
WHERE users.ID IN (
	SELECT CAST(pm2.meta_value AS SIGNED) FROM $wpdb->posts as pts
	JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
    JOIN $wpdb->postmeta as pm2 ON (pm2.post_id = pts.ID)
		WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('$review_limit_string' AS DATETIME)
			AND pm2.meta_key = "user_id"
			AND pts.post_type = "fz_product" 
			AND pts.post_status = "publish"
	GROUP BY pm.meta_value HAVING COUNT(*) > 0
) 
LIMIT $length OFFSET $start
SLQ;
                $results = $wpdb->get_results($sql);
                $Suppliers = [];
                foreach ($results as $result) {
                    $Suppliers[] = new \classes\fzSupplier((int) $result->ID);
                }

                $count_sql = <<<CPR
SELECT FOUND_ROWS()
CPR;
                $total = $wpdb->get_var($count_sql);
                $wpdb->flush();
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