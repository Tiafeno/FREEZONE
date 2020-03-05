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
class fzProduct {
    public $ID = 0;
    public $name = null;
    public $date_add = null; // Date d'ajout ou publication
    public $date_review = null; // Diernier date de mise a jour
    public $product = null;
    public $regular_price = 0; // Prix ajouter par le fournisseur
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
    public $marge_uf = 0; // Utilisateur final ou Professionel account
    public $marge_particular = 0; // Particulier
    public $marge_dealer = 0; // Revendeur
    private $user_id = 0;

    /**
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer post_id
     * @param  String context - default value 'view', possible value: view and edit
     * @return mixed
     */
    public function __construct($post_id, $context = 'view', $autoload = true) {
        if ( ! is_numeric($post_id) ) {
            $this->error = new \WP_Error('broke', "Une erreur de parametre s'est produit");
            return false;
        }
        $post_id    = intval($post_id);
        $article    = get_post($post_id);
        $this->ID   = &$post_id;
        if (!$autoload) return true;
        $this->name = $article->post_title;
        $this->regular_price = get_field('price', $post_id);
        $this->date_add      = get_field('date_add', $post_id);
        $this->date_review = get_field('date_review', $post_id);
        if ( metadata_exists( 'post', $post_id, '_fz_marge' ) ) {
            $this->marge_uf = get_post_meta( $post_id, '_fz_marge', true );
        }
        if ( metadata_exists( 'post', $post_id, '_fz_marge_dealer' ) ) {
            $this->marge_dealer = get_post_meta( $post_id, '_fz_marge_dealer', true );
        }
        if ( metadata_exists( 'post', $post_id, '_fz_marge_particular' ) ) {
            $this->marge_particular = get_post_meta( $post_id, '_fz_marge_particular', true );
        }
        $this->user_id     = get_field('user_id', $post_id);
        $this->condition   = get_post_meta( $post_id, '_fz_condition', true );
        $this->garentee    = get_post_meta( $post_id, '_fz_garentee', true );
        $this->total_sales = (int) get_field('total_sales', $post_id);
        $this->_quantity   = get_post_meta( $post_id, '_fz_quantity', true );
        $product   = get_field('product_id', $this->ID);
        $this->url = get_permalink(is_object($product) ? $product->ID : intval($product));
        if ($context === "edit") {
            $this->supplier = new fzSupplier(intval($this->user_id));
        }
    }

    public static function getInstance($post_id = 0, $context = "view", $autoload = true) {
        if (0 === $product_id) return new \WP_Error('params', "Parametre errone (product_id)");
        return new Self($post_id, $context, $autoload); // Autoload disable
    }

    public function get_id() {
        return $this->ID;
    }

    public function get_product_id() {
        $product_id = (int) get_field('product_id', $this->ID);
        return is_int($product_id) ? $product_id : 0;
    }

    public function get_user_id() {
        return (int) $this->user_id;
    }

    public function set_id($id = 0) {
        $this->ID = intval($id);
    }

    /**
     * @access public
     * @return WP_Product
     */
    public function get_product() {
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

    public function set_price($price) {
        if ( ! is_numeric($price) ) return false;
        $result = update_field('price', intval($price), $this->ID);
        $this->regular_price = intval($price);
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
        $this->garentee = $garentee;
        return $result;
    }

    public function set_condition($value) {
        if ( ! is_numeric($value) ) return false;
        $result = update_post_meta($this->ID, '_fz_condition', intval($value));
        $this->condition = intval($value);
        return $result;
    }

    // Mettre a jour la marge utilisateur final (UF)
    public function set_marge_uf($value) {
        if ( ! is_numeric($value) ) return false;
        $marge_uf = update_post_meta( $this->ID, '_fz_marge', floatval($value) );
        $this->marge_uf = $value;
        return $marge_uf;
    }

    // Mettre a jour la marge revendeur
    public function set_marge_dealer($value) {
        if ( ! is_numeric($value) ) return false;
        $marge_dealer = update_post_meta( $this->ID, '_fz_marge_dealer', floatval($value) );
        $this->marge_dealer = $value;
        return $marge_dealer;
    }

    // Mettre a jour la marge particuler
    public function set_marge_particular($value) {
        if ( ! is_numeric($value) ) return false;
        $marge_particular = update_post_meta( $this->ID, '_fz_marge_particular', floatval($value) );
        $this->marge_particular = $value;
        return $marge_particular;
    }

    public function update_date_review() {
        $date_now = date_i18n("Y-m-d H:i:s");
        $result = update_field('date_review', $date_now, $this->ID);
        $this->date_review = $date_now;
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
    // Mettre a jour la garentie si seulement la valeur n'existe pas
    if (!$fzProduct->garentee)
        $fzProduct->set_garentee(intval($_REQUEST['garentee']));
    $fzProduct->set_condition(intval($_REQUEST['condition']));
    $fzProduct->save();
    wp_send_json($fzProduct);
});

add_action('wp_ajax_mail_succeffuly_update', function() {
    if (empty($_GET['ids'])) return false;
    $paramIds = \json_decode($_GET['ids']);
    // Envoyer un mail
    do_action("fz_updated_articles_success", $paramIds);
});

// Recuperer les articles en attente en format JSON
// Utiliser dans la page de mise a jours pour les fournisseurs
add_action('wp_ajax_get_review_articles', function() {
    global $wpdb;
    
    $current_user_id = get_current_user_id();
    // Recuperer le cookie aui contient les IDS des articles en attente de mise à jours
    // $articles = isset($_GET['articles']) ? $_GET['articles'] : '';
    // if (empty($articles)) wp_send_json_success([]);

    $product_ids = [];
    $allQuantity = [];
    $orders = new \WP_Query([
        'post_type' => "shop_order",
        'post_status' => array_keys(wc_get_order_statuses()),
        "posts_per_page" => -1,
        'meta_query' => [
            [
                'key' => 'position',
                'value' => 0, // Tous les commande en attente
                'compare' => '=',
                'type' => "NUMERIC"
            ]
        ]
    ]);
    if (empty($orders->posts)) wp_send_json_error("Nothing order");

    foreach ( $orders->posts as $order ) {
        $current_order = new \WC_Order($order->ID);
        foreach ( $current_order->get_items() as $item_id => $item ) {
            $data = $item->get_data();
            $product_id = intval($data['product_id']);
            $product_ids[] = $product_id;
            if ( ! isset($allQuantity[$product_id]) ) {
                $allQuantity[ $product_id ] = (int) $data['quantity'];
                continue;
            }
            $allQuantity[ $product_id ] += (int) $data['quantity'];
        }
    }
    $product_ids_strings = implode(',', $product_ids);

    // Recuperer la date d'aujourd'hui depuis 06h du matin, car tous les articles du fournisseur qui sont "en attente"
    $now = date_i18n('Y-m-d H:i:s'); // Date actuel depuis wordpress
    $today_date_time = new \DateTime($now);
    $today_date_time->setTime(6, 0, 0); // Ajouter 06h du matin
    $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS pts.ID
FROM $wpdb->posts AS pts
WHERE
    pts.post_type = 'fz_product'
    AND pts.post_status = 'publish'
    and pts.ID in (select post_id from $wpdb->postmeta where meta_key = "product_id" and cast(meta_value as unsigned) in ($product_ids_strings))
    AND pts.ID IN (SELECT post_id FROM $wpdb->postmeta
        WHERE meta_key = 'user_id' AND cast(meta_value AS unsigned) = $current_user_id) 
    AND pts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'date_review'
            AND CAST(meta_value AS DATETIME) < CAST('{$today_date_time->format("Y-m-d H:i:s")}' AS DATETIME)
    )
SQL;

    $post_fzproducts = $wpdb->get_results($sql);
    // Boucler une a une tous les articles du fournisseur en attente
    $articles = [];
    foreach ( $post_fzproducts as $fzpost ) {
        $fz_product = new \classes\fzProduct((int) $fzpost->ID, 'view', true);
        $product_id = (int) $fz_product->get_product_id();
        $my_class = &$articles[ $product_id ]; // Pointage de memoire
        $my_class = new \stdClass();
        $my_class->quantity = $allQuantity[ $product_id ];
        $my_class->fzproduct = $fz_product;
    }

    // Récuperer les quantité demander pour cette article dans les commandes "en attente"
    // Boucler tous le commandes trouver dans la recherche
    // foreach ( $orders->posts as $order ) {
    //     $current_order = new \WC_Order($order->ID);
    //     $items = $current_order->get_items();
    //     foreach ( $items as $item_id => $item ) {
    //         $data = $item->get_data();
    //         $product_id = intval($data['product_id']);
    //         $articles[ $product_id ]->quantity += (int)$data['quantity'];
    //     }
    // }

    // Verification de quantite s'il est egale a zero (0)
    foreach ($articles as $product_id => $data) {
        $data->quantity = $data->quantity === 0 ? 1 : $data->quantity;
    }
    $articles = array_filter( array_values($articles), function($article) { return isset($article->fzproduct); });
    wp_send_json_success($articles);
});
?>