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
 */

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
    public $ID = 0;
    public $position = 0;
    public $date_add = null;
    public $user_id = 0;


    public function __construct ($order = 0) {
        parent::__construct($order);

        $this->ID = $this->get_id();

        $this->position = (int) get_field('position', $this->get_id());
        $this->date_add = $this->get_date_created();
        $this->user_id = (int) get_field('user_id', $this->get_id());
    }

    public function get_dateadd() {
        return $this->date_add;
    }

    /**
     * 0: En attente
     * 1: Envoyer
     * 2: Rejetés
     * 3: Terminée
     *
     * @return int
     */
    public function get_position() {
        return $this->position;
    }

    public function get_userid() {
        return $this->user_id;
    }

    public function get_author() {
        return new fzParticular($this->user_id);
    }

    public function update_position( $status = 0) {
        $result = update_field('position', $status, $this->get_id());
        return $result;
    }


} /* end of class fzQuotation */

?>