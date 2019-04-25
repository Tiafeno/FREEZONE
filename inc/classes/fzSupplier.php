<?php
namespace classes;

/**
 * untitledModel - class.fzSupplier.php
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
 * Short description of class fzSupplier
 *
 * @access public
 */
class fzSupplier extends \WP_User
{

    public $address = null;
    public $phone = null;
    public $company_name = null;
    public $commission = 0;

    /**
     * Short description of attribute reference
     *
     * @access public
     * @var String
     */
    public $reference = null; // F1, F2 ...


    /**
     * Short description of method __construct
     *
     * @access public
     * @author firstname and lastname of author, <author@example.org>
     * @param  Integer user_id
     * @return mixed
     */
    public function __construct( Integer $user_id)
    {
        parent::__construct($user_id);
        if (in_array('fz-supplier', $this->roles)) {
            $this->reference = "F{$this->ID}";
            $this->address = get_field('address', $this->ID);
            $this->phone = get_field('phone', $this->ID);
            $this->company_name = get_field('company_name', $this->ID);
        }
    }

} /* end of class fzSupplier */

?>