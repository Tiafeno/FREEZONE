<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 29/04/2019
 * Time: 18:51
 */

namespace classes;


class fzServices
{
    public function __construct () { }

    /**
     * @param int $product_id
     * @param array $suppliers - [{id: 0, get: 2}, ...]
     * @param int $take
     */

    // TODO: Code non tester
    public function calculate_total_product_suppliers ($product_id = 0, $suppliers = [], $take = 1)
    {
        $calculate = [];
        foreach ( $suppliers as $supplier ) {
            $has_product = $this->has_supplier_product(intval($supplier->id), $product_id);
            if (!$has_product) continue;
            if (is_array($has_product) && !empty($has_product)) {
                $article = new fzSupplierArticle($has_product[0]->ID);
                $calculate[] = [
                    'supplier_id' => intval($supplier->id),
                    'price' => $article->regular_price,
                    'stock' => $article->total_sales,
                    'took_of' => intval($supplier->get)
                ];
            }
        }

        if (empty($calculate)) return [];
        $cal_min = \__::min($calculate, function ($item) { return $item['price']; });
        $took_of = $cal_min['took_of'];
        $stock = $cal_min['stock'];
        $not_in_min =  ($took_of + $take) > $stock;
        $rest = $not_in_min ? abs(($took_of + $take) - $stock) : 0;
        if ($rest !== 0) {
            $calculate = \__::map($calculate, function ($item) use ($cal_min, &$rest) {
                if ($item['supplier_id'] === $cal_min['supplier_id']) {
                    $item['took_of'] = $cal_min['stock'];
                } else {
                    $t = $item['took_of'];
                    $s = $item['stock'];
                    $check = ($t + $rest) > $s;
                    $rest = $check ? abs(($t + $rest) - $s) : 0;

                    $item['took_of'] = $t + $rest;
                }

                return $item;
            });
        }

        return $calculate;
    }

    /**
     * @param $supplier_id
     * @param $product_id
     * @return array|bool
     */
    public function has_supplier_product ($supplier_id, $product_id)
    {
        $args = [
            'post_type' => 'fz_product',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'product_id',
                    'value' => $product_id,
                    'compare' => '='
                ],
                [
                    'key' => 'user_id',
                    'value' => $supplier_id,
                    'compare' => '='
                ]
            ]
        ];
        $fz_products = get_posts($args);
        if (is_array($fz_products) && empty($fz_products)) return false;

        return $fz_products;
    }

    public function has_supplier_stock ($supplier_id, $product_id = 0, $stock = 1)
    {
        $fz_products = $this->has_supplier_product($supplier_id, $product_id);
        $product = $fz_products ? $fz_products[0] : null;
        if (is_null($product)) return false;
        $supplier_article = new fzSupplierArticle($product->ID);

        return $supplier_article->total_sales > $stock ? true : false;
    }

}