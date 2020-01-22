<?php
namespace classes;

/**
 * Contient les informations d'un devis
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/**
 * include FZ_Item_Order
 */

/**
 * Contient les informations d'un devis
 *
 * @access public
 * @reference https://docs.woocommerce.com/wp-content/images/wc-apidocs/class-WC_Abstract_Order.html
 */
class FZ_Quote extends \WC_Order
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
    public $clientRole = null;
    public $fzItems = [];
    public $fzItemsZero = [];
    private $min_cost_with_transport = 100000;
    private $cost_transport = 12600;

    public function __construct ($order = 0) {
        parent::__construct($order);
        $this->Initialize();
    }

    private function Initialize() {
        $this->ID = $this->get_id();
        $this->position = (int) get_field('position', $this->get_id());
        $this->date_add = $this->get_date_created();
        $this->user_id = (int) get_field('user_id', $this->get_id());
        // Verifier si le meta 'client_role' n'est pas definie ou vide
        $client_role = get_post_meta( $this->ID, 'client_role', true );
        if (!$client_role || empty($client_role)) {
            $customer_id = $this->get_customer_id();
            $customer_user = new \WP_User( (int) $customer_id);
            $role = is_array($customer_user->roles) && !empty($customer_user->roles) ? $customer_user->roles[0] : null;
            update_post_meta( $this->ID, 'client_role', $role);
        }
        /**
         * Les roles des clients:
         * +fz-company
         * +fz-particular
         */
        $this->clientRole = $client_role ? $client_role : null;
        $this->fzItems = array_map(function(\WC_Order_Item_Product $item) {
            return new FZ_Item_Order($item->get_id(), $this->ID);
        }, $this->get_items());
        $this->fzItemsZero = get_post_meta( $this->ID, 'line_items_zero', true );
    }

    public function fz_items() {
        return $this->fzItems;
    }

    /**
     * Recuperer les items non disponible
     */
    public function fz_items_zero() {
        return is_array($this->fzItemsZero) ? $this->fzItemsZero : [];
    }

    public function total_net() {
        $all_total_net = array_map(function(FZ_Item_Order $item) {
            $total = $item->subtotal_net_fn();
            return ($total < $this->min_cost_with_transport && $total !== 0) ? ($this->cost_transport + $total) : $total;
        }, $this->fzItems);
        return array_sum($all_total_net);
    }

    public function total_ht() {
        $all_total_ht = array_map(function(FZ_Item_Order $item) {
            return $item->price_fn() * $item->quantity;
        }, $this->fzItems);

        return array_sum($all_total_ht);
    }

    public function date_add() {
        return $this->date_add;
    }

    public function get_min_cost_with_transport() {
        return $this->min_cost_with_transport;
    }

    public function get_cost_transport() {
        return $this->cost_transport;
    }

    /**
     * 0: En attente
     * 1: Envoyer
     * 2: Rejetés
     * 3: Acceptée
     * 4: Terminée
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
        if ($this->clientRole === "fz-particular") {
            return new fzParticular($this->user_id);
        } else {
            return new fzCompany($this->user_id);
        }
        
    }

    public function update_position( $status = 0) {
        $result = update_field('position', $status, $this->get_id());
        return $result;
    }


} /* end of class FZ_Quote */

?>