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
            // Affiche tous les articles en attente de mise à jours
            case 'review':

                $today = date_i18n('Y-m-d H:i:s');
                $review_limit = new DateTime($today);
                $review_limit->setTime(6, 0, 0); // Ajouter 06 du matin
                $dt = $review_limit->format("Y-m-d H:i:s");
                $sql = <<<SLQ
  SELECT SQL_CALC_FOUND_ROWS pm.* FROM $wpdb->posts as pts
    JOIN $wpdb->postmeta as pm ON (pm.post_id = pts.ID)
        WHERE pm.meta_key = "date_review" AND CAST(pm.meta_value AS DATETIME) < CAST('$dt' AS DATETIME)
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

                // Affiche les articles d'un fournisseur en attente de mise à jours
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
                            'value' => 0, // Tous les demandes en attente
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
                        // Recuperer tous les produits dans les demandes en attente
                        array_push($product_ids, (int) $data['product_id']);
                    }
                }
                $product_ids = array_unique($product_ids, SORT_NUMERIC );
                $join_product_ids = implode(',', $product_ids);
                if (empty($join_product_ids)) {
                    return [
                        "recordsTotal" => 0,
                        "recordsFiltered" => 0,
                        'data' => []
                    ];
                }

                global $wpdb;
                // Recuperer seulement les articles en attente
                $now = date_i18n('Y-m-d H:i:s'); // Date actuel depuis wordpress
                $today_date_time = new \DateTime($now);
                $today_date_time->setTime(6, 0, 0); // Ajouter 06h du matin

                $your_articles_request = <<<SQL
SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->posts as pts
WHERE pts.post_type = "fz_product" AND pts.post_status = "publish"
AND pts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'user_id' AND meta_value = $supplier_id)
AND pts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'product_id' AND meta_value IN ($join_product_ids))
AND pts.ID IN (SELECT post_id
        FROM $wpdb->postmeta
        WHERE meta_key = 'date_review'
            AND CAST(meta_value AS DATE) < CAST('{$today_date_time->format("Y-m-d H:i:s")}' AS DATE)
    ) 
SQL;
                $results = $wpdb->get_results($your_articles_request);
                $count_sql = "SELECT FOUND_ROWS()";
                $total = $wpdb->get_var($count_sql);
                $articles = [];

                foreach ($results as $result) {
                    $article_controller = new WP_REST_Posts_Controller('fz_product');

                    $post = get_post((int) $result->ID);
                    $response = $article_controller->prepare_item_for_response($post, new WP_REST_Request());
                    // Récuperer les données
                    $data =  $response->get_data();

                    $product_id = (int) $data['product_id'];
                    $quantity = [];
                    /**
                     * Récuperer tous les commandes en attente
                     * puis détecter et récuperer les produits de même identification pour récuperer tous la quantité demandée
                     */
                    $orders = new WP_Query([
                        'post_type' => wc_get_order_types(),
                        'post_status' => array_keys(wc_get_order_statuses()),
                        "posts_per_page" => -1,
                        'meta_query' => [
                            [
                                'key' => 'position',
                                'value' => 0, // Tous les demandes en attente
                                'compare' => '='
                            ]
                        ]
                    ]);
                    foreach ( $orders->posts as $order ) {
                        $current_order = new WC_Order($order->ID);
                        $items = $current_order->get_items();
                        foreach ( $items as $item_id => $item ) {
                            $i = $item->get_data();
                            if ($i['product_id'] === $product_id) {
                                $quantity[] = (int)$i['quantity'];
                            }
                        }
                    }

                    $qt_request = array_sum($quantity);
                    // Ajouter la quantité demandée
                    $data['quantity_request'] = $qt_request;
                    $articles[] = $data;
                }

                $wpdb->flush();
                return [
                    "recordsTotal" => $total,
                    "recordsFiltered" => $total,
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