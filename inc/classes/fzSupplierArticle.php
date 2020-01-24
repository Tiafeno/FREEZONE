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
class fzSupplierArticle
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

} /* end of class fzSupplierArticle */

?>