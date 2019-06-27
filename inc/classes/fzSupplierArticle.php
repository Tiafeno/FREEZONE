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

    public $price_dealer = 0;

    /**
     * Short description of attribute total_sales
     *
     * @access public
     * @var Integer
     */
    public $total_sales = 0;

    public $error = null;

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
        $this->price_dealer  = get_field('price_dealer', $post_id);
        $this->date_add      = get_field('date_add', $post_id);
        $this->date_review = get_field('date_review', $post_id);
        $this->total_sales = (int) get_field('total_sales', $post_id);
        $this->user_id     = get_field('user_id', $post_id);

        $product   = get_field('product_id', $this->ID);
        $this->url = get_permalink(is_object($product) ? $product->ID : intval($product));

        if ($context === "edit") {
            $this->supplier = new fzSupplier(intval($this->user_id));
        }
    }

    /**
     * Short description of method getProduct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  String sku
     * @return mixed
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
     * @author firstname and lastname of author, <author@example.org>
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

        return $result;
    }

    /**
     * Short description of method updateDateReview
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
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