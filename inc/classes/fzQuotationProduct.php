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
    public $status;
    public $error = null;
    /**
     * Contient la listes des fournisseurs sélectionner pour cette produits
     * [{id: 0, get: 2}, ...]
     * @access public
     */
    public $suppliers;
    /**
     * Short description of attribute count_item
     *
     * @access public
     * @var Integer
     */
    public $count_item = 0;
    public $item_limit = 0;
    private $product_model =  null;
    /**
     * Short description of attribute order_id
     *
     * @access public
     * @var Integer
     */
    private $order_id = 0;

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
        $this->product_model = new fzModelProduct($product_id, $order_id);
        // Récuperer les fournisseurs
        $this->suppliers = $this->product_model->get_suppliers();
        $this->status = $this->product_model->get_status();

        $order = new \WC_Order($order_id);
        $items = $order->get_items();
        foreach ($items as $item) {
            if (intval($item['product_id']) === intval($product_id)) {
                $this->count_item = $item->get_quantity();

                break;
            } else continue;
        }

        if (empty($this->count_item)) {
            $this->error = new \WP_Error('broke', 'Quantité du produit introuvable');
            return false;
        }

        // Get item limit
        if (is_array($this->suppliers) && ! empty($this->suppliers)) {
            foreach ($this->suppliers as $supplier) {
                $article = $this->product_model->get_supplier_article(intval($supplier->id), $product_id);
                if ( ! is_object($article) ) continue;
                $supplier_article = new fzSupplierArticle((int) $article->ID);
                $this->item_limit += (int) $supplier_article->total_sales;
            }
        }
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function update_status( $status = 0 ) {
        return fzModel::getInstance()->update_product_qt_status($this->order_id, $this->get_id(), $status);
    }

    public function add_supplier( $supplier ) {

    }

    public function remove_supplier( $supplier ) {

    }

} /* end of class fzQuotationProduct */

?>