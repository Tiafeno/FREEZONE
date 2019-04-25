<?php
namespace classes;

/**
 * Contient les informations d'un devis
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/**
 * include fzQuotationProduct
 *

/**
 * Contient les informations d'un devis
 *
 * @access public
 * @reference https://docs.woocommerce.com/wp-content/images/wc-apidocs/class-WC_Abstract_Order.html
 */
class fzQuotation extends \WC_Abstract_Order
{

    /**
     * Short description of attribute status
     *
     * @access public
     * @var Boolean
     */
    public $status = null;


    public function __construct ($order = 0) {
        parent::__construct($order);
    }

    /**
     * Short description of method getProducts
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @return mixed
     */
    public function get_products()
    {
        return $this->get_items();
    }

    public function update_quotation_status( $status = 0) {

    }


} /* end of class fzQuotation */

?>