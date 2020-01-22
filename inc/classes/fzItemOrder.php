<?php

namespace classes;


use model\fzModelProduct;

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}


/**
 * Short description of class FZ_Item_Order
 *
 * @access public
 */
class FZ_Item_Order {
    public $name = "";
    public $item_id = 0;
    public $status;
    public $error = null;
    public $meta_data = [];
    // Contient la listes des fournisseurs sélectionner pour cette produits
    public $suppliers = [];
    public $quantity = 0;
    public $price = 0;
    public $total = 0;
    // Cette valeur contient la condition du nombre de fournisseur ajouter
    public $multi_supplier = false;
    public $discount = 0;
    public $discount_type = 0;
    public $stock_request = 0;
    private $order_id = 0;
    private $conditions = [
        ["key" => 0, "value" => "Disponible"],
        ["key" => 1, "value" => "Rupture"],
        ["key" => 2, "value" => "Obsolete"],
        ["key" => 3, "value" => "Commande"],
    ];

    public function __construct ($item_id, $order_id) {
        $suppliers = wc_get_order_item_meta($item_id, 'suppliers', true);
        $status = wc_get_order_item_meta($item_id, 'status', true);
        $discount = wc_get_order_item_meta($item_id, 'discount', true);
        $discount_type = wc_get_order_item_meta($item_id, 'discount_type', true); // 1: Remise, 0: Aucun
        $stock_request = wc_get_order_item_meta($this->item_id, 'stock_request', true);

        $this->order_id = intval($order_id);
        $this->item_id = (int)$item_id;
        $order = new \WC_Order($this->order_id);
        $_item  = $order->get_item($item_id); // Recuperer l'item de la commande
        $this->name = $_item->get_name();
        $this->quantity = intval($_item->get_quantity());
        $this->total = intval($_item->get_total());
        $this->price = $this->total / $this->quantity;
        $this->suppliers = json_decode($suppliers);
        $this->status = intval($status);
        $this->discount = $discount ? intval($discount) : 0;
        $this->stock_request = $stock_request ? intval($stock_request) : 0;
        // Type de remise (0: Aucun ou 1: Ajouter un remise)
        $this->discount_type = $discount_type ? intval($discount_type) : 0;
    }

    public function has_stock_request () {
        $stock_request = wc_get_order_item_meta($this->item_id, 'stock_request', true);
        if (!$stock_request) return false;
        return (0 === (int)$stock_request) ? false : true;
    }

    public function stock_request_fn () {
        return $this->stock_request;
    }

    public function meta_supplier_lines_fn () {
        $lines = array_map(function ($line) {
            $condition = (int)get_post_meta(intval($line->article_id), "_fz_condition", true);
            $condition_value = is_nan($condition) ? 0 : $condition;
            $search_index = array_search($condition_value, array_column($this->conditions, 'key')); // Return key index
            $line->condition = !$search_index ? $this->conditions[0] : $this->conditions[$search_index]; // Array
            return $line;
        }, $this->suppliers);
        return $lines;
    }

    public function discount_percent_fn () {
        return (intval($this->price) * $this->discount) / 100;
    }

    public function is_qty_override_fn () {
        return $this->has_stock_request();
    }

    public function price_fn () {
        $price = $this->price;
        switch ($this->discount_type) {
            //case 2: return $price + $this->discount_percent(); // Rajout
            default: return $price;
        }
    }

    public function subtotal_net_fn () {
        $qty = 0;
        foreach ($this->meta_supplier_lines_fn() as $line) {
            switch ($line->condition['key']) {
                case 0: $qty += intval($line->get); break;
                default: break;
            }
        }
        $qty = (0 === $qty) ? $this->quantity : $qty;
        switch ($this->discount_type) {
            case 1: return ($this->price - $this->discount_percent_fn()) * $qty;
            case 0:
            default:
                return $qty * $this->price;
        }
    }
    public function get_order_id () { return $this->order_id; }
    public function qty_UI () {
        $qty = 0;
        $ui = "";
        foreach ($this->meta_supplier_lines_fn() as $line) {
            switch ($line->condition['key']) {
                case 0: $qty += intval($line->get); break;
                case 1: $ui .= "*"; break;
                case 3: $ui .= "**"; break;
                default: break;
            }
        }
        $qty = (0 === $qty) ? $this->quantity : $qty;
        return "{$qty} <span style='color: red'>{$ui}</span>span>";
    }


} /* end of class FZ_Item_Order */

?>