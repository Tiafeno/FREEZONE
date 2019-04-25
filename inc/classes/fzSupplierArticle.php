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
     * Short description of attribute sku
     *
     * @access public
     * @var String
     */
    public $sku = null;

    /**
     * Prix ajouter par le fournisseur
     *
     * @access public
     * @var Integer
     */
    public $regular_price = 0;

    /**
     * Short description of attribute total_sales
     *
     * @access public
     * @var Integer
     */
    public $total_sales = 0;

    /**
     * Short description of method __construct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer post_id
     * @return mixed
     */
    public function __construct( Integer $post_id)
    {

    }

    /**
     * Short description of method getProduct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  String sku
     * @return mixed
     */
    public function get_product( String $sku)
    {

    }

    /**
     * Short description of method setPrice
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  String price
     * @return mixed
     */
    public function set_price( String $price)
    {
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B6B begin
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B6B end
    }

    /**
     * Short description of method setSku
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  String sku
     * @return mixed
     */
    public function set_sku( String $sku)
    {
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B6E begin
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B6E end
    }


    /**
     * Short description of method updateDateReview
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  String date
     * @return mixed
     */
    public function update_date_review( String $date)
    {
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B74 begin
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B74 end
    }

} /* end of class fzSupplierArticle */

?>