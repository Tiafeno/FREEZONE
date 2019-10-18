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
    public $discount_type = 0;

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
                $this->count_item = intval($item->get_quantity());
                $this->total = intval($item->get_total());
                $this->_price = $this->total / $this->count_item;

                $suppliers = wc_get_order_item_meta( $item_id, 'suppliers', true );
                $this->suppliers = json_decode($suppliers);

                $status = wc_get_order_item_meta( $item_id, 'status', true );
                $this->status = intval($status);

                $discount = wc_get_order_item_meta( $item_id, 'discount', true );
                $this->discount = $discount ? intval($discount) : 0;

                $discount_type = wc_get_order_item_meta( $item_id, 'discount_type', true );
                $this->discount_type = $discount_type ? intval($discount_type) : 0;

                $this->item_id = (int) $item_id;
                
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

    public function discount_percent() {
        return (intval($this->_price) * $this->discount) / 100;
    }

    public function get_freezone_price() {
        $price = $this->_price;
        switch ($this->discount_type) {
            case 1:
                return $price + $this->discount_percent();
                break;
            case 2:
            default:
                return $price;
                break;
        }
    }

    public function get_freezone_subtotal() {
        $price = $this->_price;
        switch ($this->discount_type) {
            case 2:
                return $this->count_item * ($price - $this->discount_percent());
                break;
            case 1:
            case 0:
            default:
                return $this->count_item * $price;
                break;

        }
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function is_editable() {
        return $this->editable;
    }


} /* end of class fzQuotationProduct */

?>