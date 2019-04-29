<?php
namespace model;

/**
 * untitledModel - class.fzModelProduct.php
 *
 * $Id$
 *
 * This file is part of untitledModel.
 *
 * Automatically generated on 23.04.2019, 17:34:38 with ArgoUML PHP module 
 * (last revised $Date: 2010-01-12 20:14:42 +0100 (Tue, 12 Jan 2010) $)
 *
 * @author firstname and lastname of author, <author@example.org>
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/**
 * include fzQuotationProduct
 */

/**
 * Short description of class fzModelProduct
 *
 * @access public
 * @author firstname and lastname of author, <author@example.org>
 */
class fzModelProduct
{

    /**
     * Short description of attribute table
     *
     * @access public
     * @var String
     */
    protected $table = null;

    /**
     * Short description of attribute product_id
     *
     * @access public
     * @var Integer
     */
    private $product_id = null;

    /**
     * Short description of attribute status
     *
     * @access public
     * @var Boolean
     */
    private $status = null;

    /**
     * Short description of attribute order_id
     *
     * @access public
     * @var Integer
     */
    private $order_id = null;

    /**
     * Cette attributs contients les fournisseurs et leur nombre de produit
     * dans le devis
     *
     * $this->suppliers = [`supplier_id`, ...];
     *
     * @access public
     */
    private $suppliers;

    /**
     * Short description of method __construct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer product_id
     * @param  Integer order_id
     * @return mixed
     */
    public function __construct( $product_id,  $order_id )
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'quotation_product';

        $fzModel = new fzModel();
        $product_qt = $fzModel->get_product_qt((int) $order_id, (int) $product_id);
        if ( ! is_object($product_qt) ) return false;

        $this->suppliers  = unserialize($product_qt->suppliers);
        $this->product_id = intval($product_qt->product_id);
        $this->order_id   = intval($product_qt->order_id);
        $this->status     = boolval((int) $product_qt->status);
    }

    /**
     * @return bool
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function get_suppliers() {
        return $this->suppliers;
    }

    /**
     * @param $user_id
     * @param $product_id
     * @return array|null|object|void
     */
    public function get_supplier_article($user_id, $product_id) {
        global $wpdb;
        $sql = <<<SQL
SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s 
AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d) 
AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d)";
SQL;
        $prepare = $wpdb->prepare($sql, 'fz_product', 'publish', 'product_id', (int) $product_id, 'user_id', (int) $user_id);

        return $wpdb->get_row($prepare);
    }



} /* end of class fzModelProduct */

?>