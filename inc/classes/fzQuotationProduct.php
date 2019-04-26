<?php
namespace classes;


if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}


/**
 * Short description of class fzQuotationProduct
 *
 * @access public
 */
class fzQuotationProduct extends \WC_Product
{

    /**
     * Short description of attribute ID
     *
     * @access public
     * @var Integer
     */
    public $ID = null;

    /**
     * Short description of attribute orderID
     *
     * @access public
     * @var Integer
     */
    public $orderID = null;

    /**
     * Contient la listes des fournisseurs sélectionner pour cette produits
     *
     * @access public
     */
    public $suppliers;

    /**
     * Short description of attribute countItem
     *
     * @access public
     * @var Integer
     */
    public $countItem = null;

    /**
     * Short description of method getCountItem
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @return mixed
     */
    public function getCountItem()
    {

    }

    /**
     * Short description of method __construct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer product_id
     * @param  Integer order_id
     * @return mixed
     */
    public function __construct( $product_id, $order_id)
    {
        parent::__construct($product_id);
    }

} /* end of class fzQuotationProduct */

?>