<?php
namespace classes;


use model\fzModel;
use model\fzModelProduct;

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
    public $item_id = 0;
    public $status;
    public $error = null;
    /**
     * Contient la listes des fournisseurs sélectionner pour cette produits
     * @access public
     */
    public $suppliers = [];
    /**
     * Short description of attribute count_item
     *
     * @access public
     * @var Integer
     */
    public $count_item = 0;
    public $unit_cost = 0;
    public $total = 0;
    public $item_limit = 0;

    /**
     * Cette valeur contient la condition du nombre de fournisseur ajouter
     */
    public $multi_supplier = false;
    /**
     * Short description of attribute order_id
     *
     * @access public
     * @var Integer
     */
    private $order_id = 0;

    /**
     * Cette valeur est utiliser si le quantité peut être modifier par le client
     * @var bool
     */
    private $editable = true;

    /**
     * La remise pour les entreprises
     */
    public $discount = 0;
    public $has_discount = false;
    public $fake_discount = null;

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

        $this->order_id = intval($order_id);
        // Récuperer les fournisseurs

        $order = new \WC_Order($order_id);
        $items = $order->get_items();
        foreach ($items as $item_id => $item) {
            if (intval($item['product_id']) === intval($product_id)) {
                $this->count_item = $item->get_quantity();
                
                $suppliers = wc_get_order_item_meta( $item_id, 'suppliers', true );
                $this->suppliers = json_decode($suppliers);

                $status = wc_get_order_item_meta( $item_id, 'status', true );
                $this->status = intval($status);

                $discount = wc_get_order_item_meta( $item_id, 'discount', true );
                $this->discount = $discount ? $discount : 0;

                $has_discount = wc_get_order_item_meta( $item_id, 'has_discount', true );
                $this->has_discount = $has_discount ? boolval(intval($has_discount)) : true;

                $fake_discount = wc_get_order_item_meta($item_id, 'fake_discount', true);
                $this->fake_discount = $fake_discount ? $fake_discount : null;

                $this->item_id = (int) $item_id;
                $price =  intval($item->get_total()) / intval($item->get_quantity());
                $this->unit_cost = $price;
                $this->total = $item->get_total();
                break;
            } else continue;
        }

        if (empty($this->count_item)) {
            $this->error = new \WP_Error('broke', 'Produit introuvable');
            return false;
        }

        // Get item limit
        if (is_array($this->suppliers) && ! empty($this->suppliers)) {
            foreach ($this->suppliers as $supplier) {
                $supplier_article = new fzSupplierArticle((int) $supplier->article_id);
                $this->item_limit += (int) $supplier_article->total_sales;
            }
        }

        /**
         * Vérifier s'il y a plusieur fournisseur utiliser
         */
        $suppliers = array_filter($this->suppliers, function ($supplier) { return 0 !== intval($supplier->get); });
        if (is_array($suppliers) && count($suppliers) > 1) {
            $this->editable = false;
        }
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function is_editable() {
        return $this->editable;
    }

    public function update_status( $status = 0 ) {

    }

    public function add_supplier( $supplier ) {

    }

    public function remove_supplier( $supplier ) {

    }

} /* end of class fzQuotationProduct */

?>