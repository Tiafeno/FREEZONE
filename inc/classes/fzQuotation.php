<?php
namespace classes;

/**
 * Contient les informations d'un devis
 */

use model\fzModel;

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
    public $date_add = null;


    public function __construct ($order = 0) {
        parent::__construct($order);

        $fzModel = new fzModel();
        $quotation = $fzModel->get_quotation(intval($order));
        if ( ! is_object($quotation) ) return false;
        $this->status = (int)$quotation->status;
        $this->date_add = $quotation->date_add;
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

    public function update_status( $status = 0) {
        return fzModel::getInstance()->update_quotation_status($this->get_id(), $status);
    }


} /* end of class fzQuotation */

?>