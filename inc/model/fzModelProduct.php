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
    public $table = null;

    /**
     * Short description of attribute product_id
     *
     * @access public
     * @var Integer
     */
    public $product_id = null;

    /**
     * Short description of attribute status
     *
     * @access public
     * @var Boolean
     */
    public $status = null;

    /**
     * Short description of attribute order_id
     *
     * @access public
     * @var Integer
     */
    public $order_id = null;

    /**
     * Cette attributs contients les fournisseurs et leur nombre de produit
     * dans le devis
     *
     * @access public
     */
    public $suppliers;

    // --- OPERATIONS ---

    /**
     * Short description of method __construct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer product_id
     * @param  Integer order_id
     * @return mixed
     */
    public function __construct( Integer $product_id,  Integer $order_id)
    {
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B28 begin
        // section -64--88-0-102-c86f1a:16a29cc5918:-8000:0000000000000B28 end
    }

} /* end of class fzModelProduct */

?>