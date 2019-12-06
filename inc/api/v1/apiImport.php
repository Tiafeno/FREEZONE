<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 23/06/2019
 * Time: 22:26
 */

class apiImport
{

    public function __construct () { }

    /**
     * @param WP_REST_Request $request
     * @return bool
     */
    public function import_article_csv() {
        $taxonomy_cat_name = "product_cat";
        // Extraire les variables envoyer depuis la B.O
        extract($_POST, EXTR_PREFIX_SAME, 'WC');

        // Insert product cat or get it if exist
        $terms = [];

        /** @var string $categories */

        $ctg_names = explode(',', $categories);
        foreach ($ctg_names as $item) {
            $item = trim(stripslashes($item));
            $item = ucfirst($item);
            $term = term_exists($item, $taxonomy_cat_name);
            if (null === $term || 0 === $term || !$term) {
                $term = wp_insert_term($item, $taxonomy_cat_name);
                if (is_wp_error($term)) {
                    $term = get_term_by('name', $item);
                }
            }
            if (!isset($term['term_id'])) continue;
            $terms[] = (int) $term['term_id'];
        }

        // Create woocommerce product
        $options = get_field('wc', 'option');
        $woocommerce = new Automattic\WooCommerce\Client(
            "http://{$_SERVER['SERVER_NAME']}", $options['ck'], $options['cs'],
            [
                'version' => 'wc/v3'
            ]
        );

        $categorie_terms = array_map(function($ctg) { return ['id' => (int) $ctg]; }, $terms);

        /** @var string $name */
        /** @var string $regular_price */
        /** @var string $price */
        /** @var string $description */
        /** @var string $short_description */
        /** @var string $mark */
        /** @var string $marge */
        /** @var string $marge_dealer */
        /** @var string $marge_particular */
        /** @var string $reference */
        /** @var int $quantity */

        if ($is_exist = $this->product_exist($name)) {
            $product_id = $is_exist;
        } else {
            $data = [
                'name' => $name,
                'type' => 'simple',
                'regular_price' => $regular_price,
                'description'   => !empty($description) ? stripslashes($description) : '',
                'short_description' => !empty($short_description) ? stripslashes($short_description) : '',
                'categories' => $categorie_terms,
                'images' => []
            ];

            if ( ! empty($mark) && !is_null($mark)) {
                // Get attribute by identification
                $attr_id = wc_attribute_taxonomy_id_by_name('brands'); // @return int
                $data = array_merge($data, ['attributes' => [
                    [
                        'id' => $attr_id,
                        'position'  => 0,
                        'visible'   => true,
                        'variation' => false, // for variative products in case you would like to use it for variations
                        'options'   => array($mark) // if the attribute term doesn't exist, it will be created
                    ]
                ]]);
            }

            $create_product = $woocommerce->post('products', $data);
            if (empty($create_product)) wp_send_json_error("Une erreur s'est produit pendant l'ajout");
            $product_id = is_object($create_product) ? (isset($create_product->id) ? $create_product->id : null) :
                (is_array($create_product) && isset($create_product['id']) ? $create_product['id'] : null);

            if (!empty($terms)) {
                wp_set_post_terms($product_id, $terms, $taxonomy_cat_name);
            }
        }

        $Prd = new WC_Product($product_id);
        $Prd->set_sku("PRD{$product_id}");
        $Prd->save();

        $article_data = [
            'post_title'   => $name,
            'post_content' => $description,
            'post_status'  => 'publish',
            'post_type'    => 'fz_product'
        ];

        $supplier = $this->get_supplier_by_ref($reference); // WP_User

        if (!$supplier) wp_send_json_error("Fournisseur introuvable ou n'existe pas ({$reference})");

        $create_article = wp_insert_post($article_data, true);
        if (is_wp_error($create_article)) wp_send_json_error($create_article->get_error_message());
        $article_id = intval($create_article);

        $price = preg_replace('/\s+/', '', $price);

        update_field('price', $price, $article_id);
        update_field('date_add', date_i18n('Y-m-d H:i:s'), $article_id);
        update_field('date_review', date_i18n('Y-m-d H:i:s'), $article_id);
        update_field('product_id', $product_id, $article_id);
        update_field('total_sales', wc_clean($quantity), $article_id);
        update_field('user_id', $supplier->ID, $article_id);

        // Ajouter les meta dans l'article
        $meta_data = [
            [ 'key' => '_fz_marge', 'value' => trim($marge) ],
            [ 'key' => '_fz_marge_dealer', 'value' => trim($marge_dealer) ],
            [ 'key' => '_fz_marge_particular', 'value' => trim($marge_particular) ],
        ];
        foreach ($meta_data as $data) {
            update_post_meta($article_id, $data['key'], $data['value']);
        }

        if (!empty($terms)) {
            wp_set_post_terms($article_id, $terms, $taxonomy_cat_name);
        }

        wp_send_json_success("Article ajouté avec succès");
    }

    // Recuperer un fourniseur par son reference
    protected function get_supplier_by_ref($ref = null) {
        global $wpdb;
        $sql = <<<SQL
SELECT user_id FROM $wpdb->usermeta WHERE CONVERT(LOWER(`meta_key`) USING utf8mb4) = 'reference' 
  AND CONVERT(LOWER(`meta_value`) USING utf8mb4) = '{$ref}';
SQL;
        $result = $wpdb->get_row($sql);
        if (empty($result)) return false;

        $user_id = (int) $result->user_id;
        return new WP_User($user_id);
    }

    // Verifier si le produit existe
    protected function product_exist($title = '') {
        global $wpdb;

        if (empty($title)) return false;
        $title = strtolower($title);
        $sql = <<<SQL
SELECT COUNT(*) as cnt, ID FROM $wpdb->posts 
WHERE post_type = 'product'
  AND CONVERT(LOWER(`post_title`) USING utf8mb4) = '$title'
SQL;
        $result = $wpdb->get_row($sql);
        return $result->cnt >= 1 ? (int)$result->ID : false;

    }

}