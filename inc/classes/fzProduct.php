<?php
namespace classes;

/**
 * Post type (fz_product)
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/**
 * include fzSupplier
 */

/**
 * Post type (fz_product)
 *
 * @access public
 */
class fzProduct
{

    /**
     * Short description of attribute ID
     *
     * @access public
     * @var Integer
     */
    public $ID = 0;
    public $name = null;
    private $user_id = 0;

    /**
     * Short description of attribute status
     *
     * @access public
     * @var Boolean
     */
    public $status = null;

    /**
     * Short description of attribute dateAdd
     *
     * @access public
     * @var String
     */
    public $date_add = null;

    /**
     * Short description of attribute dateReview
     *
     * @access public
     * @var String
     */
    public $date_review = null;

    /**
     * Short description of attribute product
     *
     * @access public
     * @var Object
     */
    public $product = null;

    /**
     * Prix ajouter par le fournisseur
     *
     * @access public
     * @var Integer
     */
    public $regular_price = 0;


    /**
     * C'est la quantité de stock pour cette article, mais cette quantité diminue lorsque les commercial envoie un devis
     * au client.
     */
    public $total_sales = 0;
    // Quantité initial pour la gestion de stock
    public $_quantity = 0;
    public $error = null;
    public $garentee = null;
    /***
     * Cette variable est requis pour l'administrateur
     * Disponible - 0, Rupture -1, Obsolete - 2, et Commande - 3
     */
    public $condition = 0;

    /**
     * Short description of method __construct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer post_id
     * @return mixed
     */
    public function __construct($post_id, $context = 'view')
    {
        if ( ! is_numeric($post_id) ) {
            $this->error = new \WP_Error('broke', "Une erreur de parametre s'est produit");
            return false;
        }
        $post_id    = intval($post_id);
        $article    = get_post($post_id);
        $this->ID   = &$post_id;
        $this->name = $article->post_title;
        $this->regular_price = get_field('price', $post_id);
        $this->date_add      = get_field('date_add', $post_id);
        $this->date_review = get_field('date_review', $post_id);
        $this->total_sales = (int) get_field('total_sales', $post_id);
        $this->user_id     = get_field('user_id', $post_id);
        $this->condition   = get_post_meta( $post_id, '_fz_condition', true );
        $this->garentee    = get_post_meta( $post_id, '_fz_garentee', true );
        $this->_quantity   = get_post_meta( $post_id, '_fz_quantity', true );
        $product   = get_field('product_id', $this->ID);
        $this->url = get_permalink(is_object($product) ? $product->ID : intval($product));
        if ($context === "edit") {
            $this->supplier = new fzSupplier(intval($this->user_id));
        }
    }

    public function get_id() {
        return $this->ID;
    }

    public function get_product_id() {
        $product_id = (int) get_field('product_id', $this->ID);
        return is_int($product_id) ? $product_id : 0;
    }

    public function get_user_id() {
        return $this->user_id;
    }

    /**
     * Short description of method getProduct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  String sku
     * @return WP_User
     */
    public function get_product()
    {
        $post_product  = get_field('product_id', $this->ID);
        $this->product = is_object($post_product) ? $post_product : new \WC_Product(intval($post_product));
        return $this->product;
    }

    /**
     * @return int|mixed|\WP_User
     */
    public function get_author() {
        $User = is_object($this->user_id) ? $this->user_id : new \WP_User((int) $this->user_id);
        return $User;
    }

    /**
     * Short description of method setPrice
     *
     * @access public
     * @author Tiafeno Finel of author, <tiafenofnel@gmail.com>
     * @param  String price
     * @return mixed
     */
    public function set_price($price)
    {
        if ( ! is_numeric($price) ) return false;
        $result = update_field('price', intval($price), $this->ID);
        return $result;
    }

    public function set_total_sales($value) {
        if ( ! is_numeric($value) ) return false;
        $result = update_field('total_sales', intval($value), $this->ID);
        update_post_meta($this->ID, '_fz_quantity', intval($value));
        $this->_quantity = $this->total_sales = intval($value);

        return $result;
    }

    public function set_garentee($garentee) {
        if (empty($garentee)) return false;
        $result = update_post_meta( $this->ID, "_fz_garentee", $garentee );

        return $result;
    }

    public function set_condition($value) {
        if ( ! is_numeric($value) ) return false;
        $result = update_post_meta($this->ID, '_fz_condition', intval($value));
        $this->condition = intval($value);
        return $result;
    }

    /**
     * Short description of method updateDateReview
     *
     * @access public
     * @param  String date
     * @return mixed
     */
    public function update_date_review()
    {
        $date_now = date_i18n("Y-m-d H:i:s");
        $result = update_field('date_review', $date_now, $this->ID);

        return $result;
    }

    public function save() {
        return $this->update_date_review();
    }

} /* end of class fzProduct */

// Mettre a jour une article via AJAX
add_action('wp_ajax_update_fz_product', function() {
    if (empty($_REQUEST) || !isset($_REQUEST['id'])) wp_send_json_error("parametre manquant");
    $article_id = intval($_REQUEST['id']);
    $fzProduct = new \classes\fzProduct($article_id);
    $fzProduct->set_price(intval($_REQUEST['price']));
    $fzProduct->set_total_sales(intval($_REQUEST['total_sales']));
    $fzProduct->set_garentee(intval($_REQUEST['garentee']));
    $fzProduct->set_condition(intval($_REQUEST['condition']));
    $fzProduct->update_date_review();

    wp_send_json_success("Success");

});

add_action( 'rest_post_query', 'custom_topic_query', 10, 2 );
function custom_topic_query( $args, $request ) {
    if ( isset($request['search']) ) {
        $pre_meta_query = array(
            'relation' => 'OR'
        );
        $topics = explode( ',', $request['search'] );  // NOTE: Assumes comma separated taxonomies
        for ( $i = 0; $i < count( $topics ); $i++) {
            array_push( $pre_meta_query, array(
                'key' => 'company_name',
                'value' => $topics[$i],
                'compare' => "LIKE"
            ));
        }
        $meta_query = array(
            'relation' => 'AND',
            $pre_meta_query
        );
        $args[ 'meta_query' ] = $meta_query;
    }

} // end function

// Recuperer les articles en attente en format JSON
// Utiliser dans la page de mise a jours pour les fournisseurs
add_action('wp_ajax_get_review_articles', function() {
    global $wpdb;
    $fzProducts = [];
    // Recuperer le cookie aui contient les IDS des articles en attente de mise à jours
    $articles = isset($_GET['articles']) ? $_GET['articles'] : '';
    if (empty($articles)) wp_send_json_success([]);
    // Recuperer la date d'aujourd'hui depuis 06h du matin, car tous les articles sont considerer "en attente"
    // a partir de 06h du matin
    $now = date_i18n('Y-m-d H:i:s'); // Date actuel depuis wordpress
    $today_date_time = new \DateTime($now);
    $today_date_time->setTime(6, 0, 0); // Ajouter 06h du matin
    $sql = <<<CODE
SELECT SQL_CALC_FOUND_ROWS pts.ID
FROM $wpdb->posts AS pts
WHERE
    pts.ID IN ({$articles}) 
    AND pts.post_type = 'fz_product'
    AND pts.post_status = 'publish'
    AND pts.ID IN (SELECT post_id
        FROM $wpdb->postmeta
        WHERE meta_key = 'date_review'
            AND CAST(meta_value AS DATETIME) < CAST('{$today_date_time->format("Y-m-d H:i:s")}' AS DATETIME)
        )
CODE;

    $post_products = $wpdb->get_results($sql);
    // Boucler une a une les articles trouver dans la recherche
    foreach ( $post_products as $_post ) {
        $article = new \classes\fzProduct($_post->ID);
        $product_id = $article->get_product_id();
        $quantity = [];
        // Récuperer les quantité demander pour cette article dans les commandes "en attente"
        $orders = new \WP_Query([
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
            "posts_per_page" => -1,
            'meta_query' => [
                [
                    'key' => 'position',
                    'value' => 0, // Tous les commande en attente
                    'compare' => '='
                ]
            ]
        ]);
        if (empty($orders->posts)) continue;
        // Boucler tous le commandes trouver dans la recherche
        foreach ( $orders->posts as $order ) {
            $current_order = new \WC_Order($order->ID);
            $items = $current_order->get_items();
            foreach ( $items as $item_id => $item ) {
                $data = $item->get_data();
                if ($data['product_id'] === $product_id) {
                    $quantity[] = (int)$data['quantity'];
                }
            }
        }
        $article->quantity = array_sum($quantity);
        $fzProducts[] = $article;
    }
    wp_send_json_success($fzProducts);
});
?>